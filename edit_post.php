<?php

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'Не указана статья для редактирования');
    redirect(SITE_URL . 'profile.php?action=posts');
}

$post_id = (int)$_GET['id'];
$errors = [];

try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username 
        FROM posts p 
        LEFT JOIN users u ON p.author_id = u.id 
        WHERE p.id = ? AND p.author_id = ?
    ");
    $stmt->execute([$post_id, $_SESSION['user_id']]);
    $post = $stmt->fetch();
    
    if (!$post) {
        setFlashMessage('error', 'Статья не найдена или у вас нет прав для её редактирования');
        redirect(SITE_URL . 'profile.php?action=posts');
    }
    
} catch (PDOException $e) {
    setFlashMessage('error', 'Ошибка при загрузке статьи: ' . $e->getMessage());
    redirect(SITE_URL . 'profile.php?action=posts');
}

$form_data = [
    'title' => $post['title'],
    'excerpt' => $post['excerpt'],
    'content' => $post['content']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $delete_image = isset($_POST['delete_image']);
    
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
    }
    
    $image_filename = $post['image'];
    
    if ($delete_image && $image_filename) {
        $old_image_path = UPLOAD_DIR . $image_filename;
        if (file_exists($old_image_path)) {
            unlink($old_image_path);
        }
        $image_filename = null;
    }
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($image_filename) {
            $old_image_path = UPLOAD_DIR . $image_filename;
            if (file_exists($old_image_path)) {
                unlink($old_image_path);
            }
        }
        
        $validation = validateUploadedFile($_FILES['image']);
        
        if (!$validation['success']) {
            $errors[] = "Ошибка загрузки изображения: " . $validation['error'];
        } else {
            $image_filename = $validation['filename'];
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                UPDATE posts 
                SET title = ?, excerpt = ?, content = ?, image = ?, updated_at = NOW() 
                WHERE id = ? AND author_id = ?
            ");
            
            $stmt->execute([
                $title,
                $excerpt,
                $content,
                $image_filename,
                $post_id,
                $_SESSION['user_id']
            ]);
            
            if ($image_filename && isset($_FILES['image'])) {
                $upload_path = UPLOAD_DIR . $image_filename;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    throw new Exception("Не удалось сохранить изображение");
                }
            }
            
            $pdo->commit();
            
            setFlashMessage('success', 'Статья успешно обновлена!');
            redirect(SITE_URL . 'profile.php?action=posts');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Ошибка при обновлении статьи: " . $e->getMessage();
        }
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Редактирование статьи</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h5>Ошибки при обновлении статьи:</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h5><?php echo escape($post['title']); ?></h5>
                        <p class="text-muted mb-0">
                            Автор: <?php echo escape($post['username']); ?> | 
                            Создана: <?php echo formatDate($post['created_at']); ?> | 
                            Обновлена: <?php echo formatDate($post['updated_at']); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="<?php echo SITE_URL; ?>post.php?id=<?php echo $post_id; ?>" 
                           class="btn btn-outline-primary" 
                           target="_blank">
                            👁️ Просмотреть
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
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
                               maxlength="200">
                    </div>
                    
                    <div class="mb-3">
                        <label for="excerpt" class="form-label">Краткое описание</label>
                        <textarea class="form-control" 
                                  id="excerpt" 
                                  name="excerpt" 
                                  rows="2"
                                  maxlength="300"><?php echo escape($form_data['excerpt']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Содержание статьи *</label>
                        <textarea class="form-control" 
                                  id="content" 
                                  name="content" 
                                  rows="15"
                                  required><?php echo escape($form_data['content']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Текущее изображение</label>
                        <?php if ($post['image']): ?>
                            <div class="mb-2">
                                <img src="<?php echo SITE_URL . 'assets/uploads/' . $post['image']; ?>" 
                                     alt="Текущее изображение" 
                                     class="img-thumbnail" 
                                     style="max-height: 200px;">
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="delete_image" 
                                       name="delete_image">
                                <label class="form-check-label" for="delete_image">
                                    Удалить текущее изображение
                                </label>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Изображение не установлено</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Новое изображение</label>
                        <input type="file" 
                               class="form-control" 
                               id="image" 
                               name="image"
                               accept="image/*">
                        <div class="form-text">
                            Оставьте пустым, чтобы оставить текущее изображение
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <div>
                            <a href="?action=posts" class="btn btn-secondary">Отмена</a>
                            <a href="?action=index" class="btn btn-outline-secondary">В кабинет</a>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>