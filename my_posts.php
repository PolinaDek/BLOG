<?php

try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username 
        FROM posts p 
        LEFT JOIN users u ON p.author_id = u.id 
        WHERE p.author_id = ? 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $posts = $stmt->fetchAll();
    
    $total_posts = count($posts);
    
} catch (PDOException $e) {
    $error = "Ошибка при загрузке статей: " . $e->getMessage();
    $posts = [];
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Мои статьи</h1>
            <a href="?action=create" class="btn btn-success">+ Новая статья</a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (empty($posts)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <h4 class="text-muted">У вас пока нет статей</h4>
                    <p class="text-muted">Создайте свою первую статью и поделитесь знаниями с миром!</p>
                    <a href="?action=create" class="btn btn-lg btn-primary">Создать первую статью</a>
                </div>
            </div>
        <?php else: ?>
            
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Всего статей: <strong><?php echo $total_posts; ?></strong></span>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Сортировка
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?action=posts&sort=newest">Сначала новые</a></li>
                                <li><a class="dropdown-item" href="?action=posts&sort=oldest">Сначала старые</a></li>
                                <li><a class="dropdown-item" href="?action=posts&sort=title">По названию</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Заголовок</th>
                                <th>Дата создания</th>
                                <th>Дата обновления</th>
                                <th>Статус</th>
                                <th>Действия</th>
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
                                        <span class="badge bg-info ms-2">Есть фото</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($post['created_at']); ?></td>
                                <td>
                                    <?php if ($post['updated_at'] != $post['created_at']): ?>
                                        <?php echo formatDate($post['updated_at']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Не изменялась</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-success">Опубликована</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?action=edit-post&id=<?php echo $post['id']; ?>" 
                                           class="btn btn-outline-warning" 
                                           title="Редактировать">
                                            ✏️
                                        </a>
                                        <a href="?action=delete-post&id=<?php echo $post['id']; ?>" 
                                           class="btn btn-outline-danger" 
                                           title="Удалить"
                                           onclick="return confirm('Удалить статью «<?php echo addslashes($post['title']); ?>»?')">
                                            🗑️
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="card-footer text-center">
                    <small class="text-muted">
                        Для просмотра статьи нажмите на её название. 
                        Вы можете редактировать или удалять только свои статьи.
                    </small>
                </div>
            </div>
            
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="?action=index" class="btn btn-outline-secondary">Вернуться в кабинет</a>
        </div>
    </div>
</div>