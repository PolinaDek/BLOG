<?php

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'Не указана статья для удаления');
    redirect(SITE_URL . 'profile.php?action=posts');
}

$post_id = (int)$_GET['id'];

if (!isset($_GET['confirm'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.title, p.image 
            FROM posts p 
            WHERE p.id = ? AND p.author_id = ?
        ");
        $stmt->execute([$post_id, $_SESSION['user_id']]);
        $post = $stmt->fetch();
        
        if (!$post) {
            setFlashMessage('error', 'Статья не найдена или у вас нет прав для её удаления');
            redirect(SITE_URL . 'profile.php?action=posts');
        }
        
    } catch (PDOException $e) {
        setFlashMessage('error', 'Ошибка при загрузке статьи: ' . $e->getMessage());
        redirect(SITE_URL . 'profile.php?action=posts');
    }
    
    ?>
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Подтверждение удаления</h4>
                </div>
                <div class="card-body text-center">
                    <h5>Вы уверены, что хотите удалить статью?</h5>
                    <p class="lead">"<?php echo escape($post['title']); ?>"</p>
                    
                    <div class="alert alert-warning">
                        <strong>Внимание!</strong> Это действие невозможно отменить.
                        Статья будет удалена безвозвратно.
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="?action=delete-post&id=<?php echo $post_id; ?>&confirm=yes" 
                           class="btn btn-danger">
                            Да, удалить
                        </a>
                        <a href="?action=posts" class="btn btn-secondary">Отмена</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    exit();
}

if ($_GET['confirm'] === 'yes') {
    try {
        $stmt = $pdo->prepare("SELECT image FROM posts WHERE id = ? AND author_id = ?");
        $stmt->execute([$post_id, $_SESSION['user_id']]);
        $post = $stmt->fetch();
        
        if (!$post) {
            setFlashMessage('error', 'Статья не найдена или у вас нет прав для её удаления');
            redirect(SITE_URL . 'profile.php?action=posts');
        }
        
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND author_id = ?");
        $stmt->execute([$post_id, $_SESSION['user_id']]);
        
        if ($post['image']) {
            $image_path = UPLOAD_DIR . $post['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $pdo->commit();
        
        setFlashMessage('success', 'Статья успешно удалена!');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        setFlashMessage('error', 'Ошибка при удалении статьи: ' . $e->getMessage());
    }
    
    redirect(SITE_URL . 'profile.php?action=posts');
}
?>