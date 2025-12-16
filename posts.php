<?php
require_once 'includes/admin_auth.php';

$action = $_GET['action'] ?? 'list';
$page_title = "Управление статьями";

switch ($action) {
    case 'create':
        $page_title = "Создание статьи";
        break;
    case 'edit':
        $page_title = "Редактирование статьи";
        break;
    case 'delete':
        $page_title = "Удаление статьи";
        break;
    default:
        $page_title = "Все статьи";
}

adminHeader($page_title);

switch ($action) {
    case 'create':
        ?>
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0">Создание новой статьи</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="posts.php?action=save" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="title" class="form-label">Заголовок статьи *</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="content" class="form-label">Содержание *</label>
                                <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Дополнительные параметры</h6>
                                    
                                    <div class="mb-3">
                                        <label for="author_id" class="form-label">Автор</label>
                                        <select class="form-select" id="author_id" name="author_id" required>
                                            <option value="">Выберите автора</option>
                                            <?php
                                            $stmt = $pdo->query("SELECT id, username FROM users ORDER BY username");
                                            while ($user = $stmt->fetch()) {
                                                $selected = ($user['id'] == $_SESSION['user_id']) ? 'selected' : '';
                                                echo "<option value='{$user['id']}' {$selected}>{$user['username']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="excerpt" class="form-label">Краткое описание</label>
                                        <textarea class="form-control" id="excerpt" name="excerpt" rows="3"></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Изображение</label>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">Опубликовать</button>
                                        <a href="posts.php" class="btn btn-secondary">Отмена</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php
        break;
        
    case 'edit':
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            echo '<div class="alert alert-danger">Не указана статья для редактирования</div>';
            echo '<a href="posts.php" class="btn btn-secondary">Назад</a>';
            break;
        }
        
        $post_id = (int)$_GET['id'];
        
        try {
            $stmt = $pdo->prepare("
                SELECT p.*, u.username 
                FROM posts p 
                LEFT JOIN users u ON p.author_id = u.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$post_id]);
            $post = $stmt->fetch();
            
            if (!$post) {
                echo '<div class="alert alert-danger">Статья не найдена</div>';
                echo '<a href="posts.php" class="btn btn-secondary">Назад</a>';
                break;
            }
            ?>
            <div class="card admin-card">
                <div class="card-header">
                    <h5 class="mb-0">Редактирование статьи</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="posts.php?action=update&id=<?php echo $post_id; ?>" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Заголовок статьи *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo escape($post['title']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="content" class="form-label">Содержание *</label>
                                    <textarea class="form-control" id="content" name="content" rows="10" required><?php echo escape($post['content']); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6>Дополнительные параметры</h6>
                                        
                                        <div class="mb-3">
                                            <label for="author_id" class="form-label">Автор</label>
                                            <select class="form-select" id="author_id" name="author_id" required>
                                                <option value="">Выберите автора</option>
                                                <?php
                                                $stmt = $pdo->query("SELECT id, username FROM users ORDER BY username");
                                                while ($user = $stmt->fetch()) {
                                                    $selected = ($user['id'] == $post['author_id']) ? 'selected' : '';
                                                    echo "<option value='{$user['id']}' {$selected}>{$user['username']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="excerpt" class="form-label">Краткое описание</label>
                                            <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?php echo escape($post['excerpt']); ?></textarea>
                                        </div>
                                        
                                        <?php if ($post['image']): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Текущее изображение</label>
                                            <div>
                                                <img src="<?php echo SITE_URL; ?>assets/uploads/<?php echo $post['image']; ?>" 
                                                     alt="Изображение" 
                                                     class="img-thumbnail" 
                                                     style="max-width: 100%;">
                                            </div>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" id="delete_image" name="delete_image">
                                                <label class="form-check-label" for="delete_image">
                                                    Удалить изображение
                                                </label>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="mb-3">
                                            <label for="image" class="form-label">Новое изображение</label>
                                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                                            <a href="posts.php" class="btn btn-secondary">Отмена</a>
                                        </div>
                                        
                                        <hr class="my-3">
                                        
                                        <div class="small text-muted">
                                            <p><strong>Информация:</strong></p>
                                            <p>Создана: <?php echo formatDate($post['created_at']); ?></p>
                                            <p>Обновлена: <?php echo formatDate($post['updated_at']); ?></p>
                                            <p>Автор: <?php echo escape($post['username']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php
            
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Ошибка при загрузке статьи: ' . $e->getMessage() . '</div>';
        }
        break;
        
    case 'delete':
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            echo '<div class="alert alert-danger">Не указана статья для удаления</div>';
            echo '<a href="posts.php" class="btn btn-secondary">Назад</a>';
            break;
        }
        
        $post_id = (int)$_GET['id'];
        
        if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
            try {
                $stmt = $pdo->prepare("SELECT image FROM posts WHERE id = ?");
                $stmt->execute([$post_id]);
                $post = $stmt->fetch();
                
                $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                $stmt->execute([$post_id]);
                
                if ($post && $post['image']) {
                    $image_path = __DIR__ . '/../../assets/uploads/' . $post['image'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                
                setFlashMessage('success', 'Статья успешно удалена');
                redirect(ADMIN_URL . 'posts.php');
                
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Ошибка при удалении статьи: ' . $e->getMessage() . '</div>';
            }
        } else {
            ?>
            <div class="card admin-card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Подтверждение удаления</h5>
                </div>
                <div class="card-body text-center">
                    <div class="alert alert-warning">
                        <h5>Вы уверены, что хотите удалить эту статью?</h5>
                        <p>Это действие нельзя отменить.</p>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="posts.php?action=delete&id=<?php echo $post_id; ?>&confirm=yes" 
                           class="btn btn-danger">
                            Да, удалить
                        </a>
                        <a href="posts.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </div>
            </div>
            <?php
        }
        break;
        
    default:
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM posts");
            $total_posts = $stmt->fetch()['total'];
            $total_pages = ceil($total_posts / $limit);
            
            $stmt = $pdo->prepare("
                SELECT p.*, u.username 
                FROM posts p 
                LEFT JOIN users u ON p.author_id = u.id 
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $posts = $stmt->fetchAll();
            
            ?>
            <div class="card admin-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Все статьи (<?php echo $total_posts; ?>)</h5>
                    <a href="posts.php?action=create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>
                        Новая статья
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($posts)): ?>
                        <div class="text-center py-5">
                            <h5 class="text-muted">Статьи пока не добавлены</h5>
                            <a href="posts.php?action=create" class="btn btn-primary mt-3">Создать первую статью</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="5%">ID</th>
                                        <th>Заголовок</th>
                                        <th width="15%">Автор</th>
                                        <th width="15%">Дата</th>
                                        <th width="15%">Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td><?php echo $post['id']; ?></td>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>post.php?id=<?php echo $post['id']; ?>" class="text-decoration-none">
                                                <?php echo truncateText(escape($post['title']), 50); ?>
                                            </a>
                                            <?php if ($post['image']): ?>
                                                <span class="badge bg-info ms-1">Фото</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo escape($post['username']); ?></td>
                                        <td><?php echo formatDate($post['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="posts.php?action=edit&id=<?php echo $post['id']; ?>" 
                                                   class="btn btn-outline-warning" 
                                                   title="Редактировать">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="posts.php?action=delete&id=<?php echo $post['id']; ?>" 
                                                   class="btn btn-outline-danger" 
                                                   title="Удалить">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Пагинация">
                            <ul class="pagination justify-content-center mt-4">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="posts.php?page=<?php echo $page - 1; ?>">Назад</a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="posts.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="posts.php?page=<?php echo $page + 1; ?>">Вперед</a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                </div>
            </div>
            <?php
            
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Ошибка при загрузке статей: ' . $e->getMessage() . '</div>';
        }
        break;
}

adminFooter();
?>