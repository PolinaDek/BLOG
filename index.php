<?php


require_once 'includes/config.php';

$page_title = "Главная страница";

try {
    $stmt = $pdo->query("
        SELECT p.*, u.username 
        FROM posts p 
        LEFT JOIN users u ON p.author_id = u.id 
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Ошибка при получении статей: " . $e->getMessage();
    $posts = [];
}

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <h1 class="mb-4">Последние статьи</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (empty($posts)): ?>
            <div class="alert alert-info">
                Статьи еще не добавлены. <?php if (isLoggedIn()): ?>
                    <a href="profile.php?action=create">Создайте первую статью</a>.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="card mb-4">
                    <?php if ($post['image']): ?>
                        <img src="<?php echo SITE_URL . 'assets/uploads/' . $post['image']; ?>" 
                             class="card-img-top" 
                             alt="<?php echo escape($post['title']); ?>"
                             style="max-height: 300px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <h2 class="card-title">
                            <a href="post.php?id=<?php echo $post['id']; ?>" class="text-decoration-none">
                                <?php echo escape($post['title']); ?>
                            </a>
                        </h2>
                        <p class="card-text">
                            <?php 
                            $excerpt = $post['excerpt'] ?: strip_tags($post['content']);
                            echo mb_substr($excerpt, 0, 200) . '...';
                            ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted">
    <small>
        Автор: <?php echo escape($post['username']); ?> | 
        Дата: <?php echo date('d.m.Y H:i', strtotime($post['created_at'])); ?>
        <?php
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE post_id = ?");
            $stmt->execute([$post['id']]);
            $comment_count = $stmt->fetch()['count'];
            
            if ($comment_count > 0) {
                echo ' | <i class="bi bi-chat"></i> ' . $comment_count;
            }
        } catch (Exception $e) {
        }
        ?>
    </small>
</div>
                           <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-primary">Читать далее</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>О блоге</h5>
            </div>
            <div class="card-body">
                <p>Добро пожаловать в наш блог! Здесь вы найдете интересные статьи на разные темы.</p>
                <?php if (!isLoggedIn()): ?>
                    <div class="alert alert-warning">
                        <strong>Внимание!</strong> Для создания статей необходимо 
                        <a href="register.php">зарегистрироваться</a> или 
                        <a href="login.php">войти</a>.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>