<?php

$errors = [];
$form_data = [
    'title' => '',
    'excerpt' => '',
    'content' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    $form_data['title'] = $title;
    $form_data['excerpt'] = $excerpt;
    $form_data['content'] = $content;
    
    if (empty($title)) {
        $errors[] = "Заголовок статьи обязателен";
    } elseif (strlen($title) < 5) {
        $errors[] = "Заголовок должен содержать минимум 5 символов";
    }
    
    if (empty($content)) {
        $errors[] = "Содержание статьи обязательно";
    } elseif (strlen($content) < 50) {
        $errors[] = "Статья должна содержать минимум 50 символов";
    }
    
    if (empty($excerpt)) {
        $excerpt = truncateText(strip_tags($content), 150, '');
    }
    
    $image_filename = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $validation = validateUploadedFile($_FILES['image']);
        
        if (!$validation['success']) {
            $errors[] = "Ошибка загрузки изображения: " . $validation['error'];
        } else {
            $image_filename = $validation['filename'];
            
            if (!is_dir(UPLOAD_DIR)) {
                if (!mkdir(UPLOAD_DIR, 0777, true)) {
                    $errors[] = "Не удалось создать папку для загрузки изображений";
                }
            }
            
            if (is_dir(UPLOAD_DIR) && !is_writable(UPLOAD_DIR)) {
                $errors[] = "Папка для загрузки недоступна для записи";
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO posts (title, excerpt, content, author_id, image) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $title,
                $excerpt,
                $content,
                $_SESSION['user_id'],
                $image_filename
            ]);
            
            $post_id = $pdo->lastInsertId();
            
            if ($image_filename) {
                $upload_path = UPLOAD_DIR . $image_filename;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $upload_error = error_get_last();
                    $error_message = $upload_error ? $upload_error['message'] : 'Неизвестная ошибка';
                    throw new Exception("Не удалось сохранить изображение: " . $error_message);
                }
                
                if (!file_exists($upload_path)) {
                    throw new Exception("Файл изображения не был сохранен на сервер");
                }
            }
            
            $pdo->commit();
            
            setFlashMessage('success', 'Статья успешно создана!');
            redirect(SITE_URL . 'profile.php?action=posts');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Ошибка при создании статьи: " . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>profile.php">Личный кабинет</a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>profile.php?action=posts">Мои статьи</a></li>
                <li class="breadcrumb-item active" aria-current="page">Новая статья</li>
            </ol>
        </nav>
        
        <div class="mb-4">
            <h1>Создание новой статьи</h1>
            <p class="text-muted">Поделитесь своими знаниями с читателями</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h5>Ошибки при создании статьи:</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Заполните информацию о статье</h5>
            </div>
            
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Заголовок статьи *</label>
                        <input type="text" 
                               class="form-control" 
                               id="title" 
                               name="title" 
                               value="<?php echo escape($form_data['title']); ?>"
                               required
                               minlength="5"
                               maxlength="200"
                               placeholder="Введите заголовок статьи">
                        <div class="form-text">Минимум 5, максимум 200 символов</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="excerpt" class="form-label">Краткое описание</label>
                        <textarea class="form-control" 
                                  id="excerpt" 
                                  name="excerpt" 
                                  rows="2"
                                  maxlength="300"
                                  placeholder="Краткое описание, которое будет отображаться на главной странице"><?php echo escape($form_data['excerpt']); ?></textarea>
                        <div class="form-text">Максимум 300 символов. Если оставить пустым, будет создано автоматически</div>
                    </div>
                
                    <div class="mb-3">
                        <label for="content" class="form-label">Содержание статьи *</label>
                        <textarea class="form-control" 
                                  id="content" 
                                  name="content" 
                                  rows="12"
                                  required
                                  placeholder="Напишите содержание вашей статьи здесь..."><?php echo escape($form_data['content']); ?></textarea>
                        <div class="form-text">Основной текст статьи</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="image" class="form-label">Изображение (обложка)</label>
                        <input type="file" 
                               class="form-control" 
                               id="image" 
                               name="image"
                               accept="image/*">
                        <div class="form-text">
                            Максимальный размер: <?php echo (UPLOAD_MAX_SIZE / 1024 / 1024); ?>MB. 
                            Разрешены: JPG, PNG, GIF, WebP
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <div>
                            <a href="<?php echo SITE_URL; ?>profile.php?action=posts" class="btn btn-secondary">Отмена</a>
                            <a href="<?php echo SITE_URL; ?>profile.php" class="btn btn-outline-secondary">В кабинет</a>
                        </div>
                        <button type="submit" class="btn btn-primary">Опубликовать статью</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>