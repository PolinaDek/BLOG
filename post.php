<?php
require_once 'includes/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'Статья не найдена');
    redirect(SITE_URL);
}

$post_id = (int)$_GET['id'];
$errors = [];

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
        setFlashMessage('error', 'Статья не найдена');
        redirect(SITE_URL);
    }
    
} catch (PDOException $e) {
    setFlashMessage('error', 'Ошибка при загрузке статьи: ' . $e->getMessage());
    redirect(SITE_URL);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    if (!isLoggedIn()) {
        $errors[] = "Для добавления комментария необходимо авторизоваться";
    } else {
        $content = trim($_POST['content'] ?? '');
        
        if (empty($content)) {
            $errors[] = "Комментарий не может быть пустым";
        } elseif (strlen($content) < 3) {
            $errors[] = "Комментарий должен содержать минимум 3 символа";
        } elseif (strlen($content) > 1000) {
            $errors[] = "Комментарий слишком длинный (максимум 1000 символов)";
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO comments (post_id, user_id, content) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$post_id, $_SESSION['user_id'], $content]);
                
                setFlashMessage('success', 'Комментарий добавлен');
                redirect(SITE_URL . 'post.php?id=' . $post_id);
                
            } catch (PDOException $e) {
                $errors[] = "Ошибка при добавлении комментария: " . $e->getMessage();
            }
        }
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username 
        FROM comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE c.post_id = ? 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll();
    $comments_count = count($comments);
    
} catch (PDOException $e) {
    $comments = [];
    $comments_count = 0;
}

$page_title = $post['title'];
require_once 'includes/header.php';
?>

<style>
/* Стили для комментариев */
.comments-section {
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

.comment {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    background-color: #f8f9fa;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e9ecef;
}

.comment-author {
    font-weight: bold;
    color: #2c3e50;
}

.comment-date {
    color: #6c757d;
    font-size: 0.9em;
}

.comment-content {
    line-height: 1.5;
    margin-bottom: 10px;
}

.comment-form {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.comment-form textarea {
    resize: vertical;
    min-height: 100px;
}

.no-comments {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.comments-count {
    font-size: 1.2em;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 20px;
}
</style>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Главная</a></li>
                <li class="breadcrumb-item active"><?php echo truncateText(escape($post['title']), 30); ?></li>
            </ol>
        </nav>
        
        <article>
            <header class="mb-4">
                <h1 class="mb-3"><?php echo escape($post['title']); ?></h1>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="text-muted">
                        <i class="bi bi-person me-1"></i>
                        <strong><?php echo escape($post['username']); ?></strong>
                        <span class="mx-2">•</span>
                        <i class="bi bi-calendar me-1"></i>
                        <?php echo formatDate($post['created_at']); ?>
                        <?php if ($post['updated_at'] != $post['created_at']): ?>
                            <span class="mx-2">•</span>
                            <small>(обновлено: <?php echo formatDate($post['updated_at']); ?>)</small>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isLoggedIn() && (isAdmin() || $_SESSION['user_id'] == $post['author_id'])): ?>
                    <div class="btn-group">
                        <a href="<?php echo SITE_URL; ?>profile.php?action=edit-post&id=<?php echo $post_id; ?>" 
                           class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-pencil me-1"></i>
                            Редактировать
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </header>
            
            <?php if ($post['image']): ?>
            <div class="mb-4 text-center">
                <img src="<?php echo SITE_URL; ?>assets/uploads/<?php echo $post['image']; ?>" 
                     alt="<?php echo escape($post['title']); ?>" 
                     class="img-fluid rounded" 
                     style="max-width: 100%; height: auto;">
                <?php if ($post['excerpt']): ?>
                    <p class="text-muted text-center mt-2"><small><?php echo escape($post['excerpt']); ?></small></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="article-content mb-5" style="
                word-wrap: break-word;
                overflow-wrap: break-word;
                word-break: break-word;
                white-space: pre-wrap;
                line-height: 1.6;
                font-size: 16px;
            ">
                <?php echo nl2br(escape($post['content'])); ?>
            </div>
            
            <div class="card mt-5">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px;">
                                <i class="bi bi-person" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="card-title mb-1"><?php echo escape($post['username']); ?></h5>
                            <p class="card-text text-muted mb-0">Автор статьи</p>
                        </div>
                    </div>
                </div>
            </div>
        </article>
        
        <div class="comments-section">
            <h4 class="mb-4">
                <i class="bi bi-chat-text me-2"></i>
                Комментарии 
                <span class="badge bg-secondary"><?php echo $comments_count; ?></span>
            </h4>
            
            <?php if (isLoggedIn()): ?>
            <div class="comment-form">
                <h5>Добавить комментарий</h5>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="content" class="form-label">Ваш комментарий</label>
                        <textarea class="form-control" id="content" name="content" rows="4" 
                                  placeholder="Напишите ваш комментарий..." 
                                  maxlength="1000" required></textarea>
                        <div class="form-text">Максимум 1000 символов</div>
                    </div>
                    <button type="submit" name="add_comment" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>
                        Отправить комментарий
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Внимание!</strong> Для добавления комментария необходимо 
                <a href="<?php echo SITE_URL; ?>login.php">войти</a> или 
                <a href="<?php echo SITE_URL; ?>register.php">зарегистрироваться</a>.
            </div>
            <?php endif; ?>
            
            <div class="comments-list mt-4">
                <?php if (empty($comments)): ?>
                    <div class="no-comments">
                        <i class="bi bi-chat-left" style="font-size: 3rem; color: #dee2e6;"></i>
                        <h5 class="mt-3">Комментариев пока нет</h5>
                        <p class="text-muted">Будьте первым, кто оставит комментарий!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <div class="comment-header">
                            <div class="comment-author">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo escape($comment['username']); ?>
                            </div>
                            <div class="comment-date">
                                <i class="bi bi-clock me-1"></i>
                                <?php echo formatDate($comment['created_at']); ?>
                            </div>
                        </div>
                        <div class="comment-content">
                            <?php echo nl2br(escape($comment['content'])); ?>
                        </div>
                        <?php if (isAdmin() || (isLoggedIn() && $_SESSION['user_id'] == $comment['user_id'])): ?>
                        <div class="comment-actions text-end">
                            <small>
                                <a href="?delete_comment=<?php echo $comment['id']; ?>" 
                                   class="text-danger"
                                   onclick="return confirm('Удалить комментарий?')">
                                    <i class="bi bi-trash"></i> Удалить
                                </a>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="d-flex justify-content-between mt-5">
            <a href="<?php echo SITE_URL; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>
                На главную
            </a>
            
            <?php if (isLoggedIn()): ?>
                <div class="btn-group">
                    <?php if (isAdmin() || $_SESSION['user_id'] == $post['author_id']): ?>
                    <a href="<?php echo SITE_URL; ?>profile.php?action=edit-post&id=<?php echo $post_id; ?>" 
                       class="btn btn-outline-warning">
                        <i class="bi bi-pencil me-1"></i>
                        Редактировать статью
                    </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo SITE_URL; ?>profile.php?action=posts" class="btn btn-outline-primary">
                        <i class="bi bi-list-ul me-1"></i>
                        К списку статей
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>