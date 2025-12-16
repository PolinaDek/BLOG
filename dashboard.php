<?php

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as post_count FROM posts WHERE author_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $post_count = $stmt->fetch()['post_count'];
    
    $stmt = $pdo->prepare("
        SELECT id, title, created_at, image 
        FROM posts 
        WHERE author_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_posts = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Ошибка при загрузке данных: " . $e->getMessage();
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="mb-4">
            <h1>Личный кабинет</h1>
            <p class="text-muted">Управление вашим профилем и статьями</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Информация о профиле</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Имя пользователя:</th>
                                <td><?php echo escape($_SESSION['username']); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo escape($_SESSION['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Роль:</th>
                                <td>
                                    <span class="badge bg-<?php echo $_SESSION['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo $_SESSION['role'] === 'admin' ? 'Администратор' : 'Пользователь'; ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="d-grid gap-2">
                            <a href="?action=edit" class="btn btn-primary">
                                Редактировать профиль
                            </a>
                            <a href="<?php echo SITE_URL; ?>logout.php" class="btn btn-outline-danger">
                                Выйти из системы
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <h2 class="display-4"><?php echo $post_count; ?></h2>
                    <p>Статей</p>
                    <a href="?action=posts" class="btn btn-outline-primary btn-sm">Все статьи</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h2 class="display-4">0</h2>
                    <p>Комментариев</p>
                    <button class="btn btn-outline-secondary btn-sm" disabled>Скоро</button>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h2 class="display-4"><?php echo date('d.m.Y'); ?></h2>
                    <p>Дата сегодня</p>
                    <a href="<?php echo SITE_URL; ?>" class="btn btn-outline-success btn-sm">На главную</a>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Быстрые действия</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="?action=create" class="btn btn-success w-100">
                            Написать статью
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="?action=posts" class="btn btn-primary w-100">
                            Мои статьи
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="?action=edit" class="btn btn-outline-primary w-100">
                            Редактировать профиль
                        </a>
                    </div>
                    <div class="col-md-3">
                        <?php if (isAdmin()): ?>
                            <a href="<?php echo ADMIN_URL; ?>" class="btn btn-danger w-100">
                                Админ-панель
                            </a>
                        <?php else: ?>
                            <button class="btn btn-outline-secondary w-100" disabled>
                                Только для админов
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Последние статьи</h5>
                <span class="badge bg-secondary"><?php echo count($recent_posts); ?> из <?php echo $post_count; ?></span>
            </div>
            <div class="card-body">
                <?php if (empty($recent_posts)): ?>
                    <div class="text-center py-4">
                        <p class="text-muted mb-3">У вас пока нет статей</p>
                        <a href="?action=create" class="btn btn-primary">Создать первую статью</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Заголовок</th>
                                    <th width="20%">Дата создания</th>
                                    <th width="15%">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_posts as $post): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>post.php?id=<?php echo $post['id']; ?>" class="text-decoration-none">
                                            <?php echo truncateText(escape($post['title']), 60); ?>
                                        </a>
                                        <?php if ($post['image']): ?>
                                            <span class="badge bg-info ms-2">Фото</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($post['created_at']); ?></td>
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
                                               onclick="return confirm('Удалить статью?')">
                                                🗑️
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="?action=posts" class="btn btn-outline-secondary">Все статьи</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Полезная информация</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Советы по написанию статей:</h6>
                        <ul>
                            <li>Пишите четко и по делу</li>
                            <li>Используйте заголовки и подзаголовки</li>
                            <li>Добавляйте изображения для наглядности</li>
                            <li>Проверяйте орфографию</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Статистика системы:</h6>
                        <ul>
                            <li>Всего пользователей: <strong><?php 
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
                                echo $stmt->fetch()['count'];
                            ?></strong></li>
                            <li>Всего статей: <strong><?php 
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts");
                                echo $stmt->fetch()['count'];
                            ?></strong></li>
                            <li>Ваш ID: <strong><?php echo $_SESSION['user_id']; ?></strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>