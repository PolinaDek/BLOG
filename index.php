<?php
require_once 'includes/admin_auth.php';

$page_title = "Дашборд";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $total_users = $stmt->fetch()['total_users'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_admins FROM users WHERE role = 'admin'");
    $total_admins = $stmt->fetch()['total_admins'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_posts FROM posts");
    $total_posts = $stmt->fetch()['total_posts'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as today_posts FROM posts WHERE DATE(created_at) = CURDATE()");
    $today_posts = $stmt->fetch()['today_posts'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_comments FROM comments");
    $total_comments = $stmt->fetch()['total_comments'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as today_comments FROM comments WHERE DATE(created_at) = CURDATE()");
    $today_comments = $stmt->fetch()['today_comments'];
    
    $comments_table_exists = true;
    try {
        $pdo->query("SELECT 1 FROM comments LIMIT 1");
    } catch (Exception $e) {
        $comments_table_exists = false;
        $total_comments = 0;
        $today_comments = 0;
    }
    
    $stmt = $pdo->query("
        SELECT p.*, u.username 
        FROM posts p 
        LEFT JOIN users u ON p.author_id = u.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $recent_posts = $stmt->fetchAll();
    
    $stmt = $pdo->query("
        SELECT id, username, email, role, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent_users = $stmt->fetchAll();
    
    if ($comments_table_exists) {
        $stmt = $pdo->query("
            SELECT c.*, u.username as author_name, p.title as post_title
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            LEFT JOIN posts p ON c.post_id = p.id
            ORDER BY c.created_at DESC 
            LIMIT 5
        ");
        $recent_comments = $stmt->fetchAll();
    } else {
        $recent_comments = [];
    }
    
} catch (PDOException $e) {
    $error = "Ошибка при загрузке статистики: " . $e->getMessage();
}

adminHeader($page_title);
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card-admin">
            <h6>Всего пользователей</h6>
            <div class="number"><?php echo $total_users; ?></div>
            <small><?php echo $total_admins; ?> администраторов</small>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card-admin" style="border-left-color: #2ecc71;">
            <h6>Всего статей</h6>
            <div class="number"><?php echo $total_posts; ?></div>
            <small><?php echo $today_posts; ?> сегодня</small>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card-admin" style="border-left-color: #e74c3c;">
            <h6>Комментарии</h6>
            <div class="number"><?php echo $total_comments; ?></div>
            <small><?php echo $today_comments; ?> сегодня</small>
            <?php if (!$comments_table_exists): ?>
                <div class="text-danger small mt-1">
                    <i class="bi bi-exclamation-triangle"></i> Таблица комментариев не создана
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card-admin" style="border-left-color: #f39c12;">
            <h6>Активность</h6>
            <div class="number"><?php echo date('H:i'); ?></div>
            <small><?php echo date('d.m.Y'); ?></small>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card admin-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Последние статьи</h5>
                <a href="posts.php" class="btn btn-sm btn-primary">Все статьи</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_posts)): ?>
                    <p class="text-muted text-center py-3">Статьи пока не добавлены</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Заголовок</th>
                                    <th width="30%">Автор</th>
                                    <th width="20%">Дата</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_posts as $post): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>post.php?id=<?php echo $post['id']; ?>" class="text-decoration-none">
                                            <?php echo truncateText(escape($post['title']), 30); ?>
                                        </a>
                                    </td>
                                    <td><?php echo escape($post['username']); ?></td>
                                    <td><?php echo formatDate($post['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card admin-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Последние пользователи</h5>
                <a href="users.php" class="btn btn-sm btn-primary">Все пользователи</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_users)): ?>
                    <p class="text-muted text-center py-3">Пользователи пока не зарегистрированы</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Имя</th>
                                    <th>Email</th>
                                    <th width="25%">Роль</th>
                                    <th width="25%">Дата</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?php echo escape($user['username']); ?></td>
                                    <td><?php echo escape($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                            <?php echo $user['role'] === 'admin' ? 'Админ' : 'Пользователь'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($user['created_at'], 'd.m.Y'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card admin-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Последние комментарии</h5>
                <?php if ($comments_table_exists): ?>
                    <a href="comments.php" class="btn btn-sm btn-primary">Все комментарии</a>
                <?php else: ?>
                    <button class="btn btn-sm btn-warning" onclick="createCommentsTable()">
                        <i class="bi bi-gear me-1"></i>
                        Создать таблицу
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$comments_table_exists): ?>
                    <div class="alert alert-warning">
                        <h6><i class="bi bi-exclamation-triangle me-2"></i>Таблица комментариев не создана</h6>
                        <p class="mb-0">Для работы с комментариями необходимо создать таблицу в базе данных.</p>
                        <div class="mt-3">
                            <button class="btn btn-primary btn-sm" onclick="createCommentsTable()">
                                <i class="bi bi-plus-circle me-1"></i>
                                Создать таблицу комментариев
                            </button>
                            <button class="btn btn-outline-secondary btn-sm ms-2" onclick="showSqlQuery()">
                                <i class="bi bi-code me-1"></i>
                                Показать SQL запрос
                            </button>
                        </div>
                        <div id="sqlQuery" class="mt-3" style="display: none;">
                            <div class="card">
                                <div class="card-body">
                                    <h6>SQL запрос для создания таблицы:</h6>
                                    <pre class="bg-light p-3 rounded"><code>CREATE TABLE IF NOT EXISTS comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif (empty($recent_comments)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-chat" style="font-size: 3rem; color: #dee2e6;"></i>
                        <h5 class="mt-3 text-muted">Комментариев пока нет</h5>
                        <p class="text-muted">Пользователи еще не оставляли комментарии</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th>Комментарий</th>
                                    <th width="15%">Автор</th>
                                    <th width="20%">Статья</th>
                                    <th width="15%">Дата</th>
                                    <th width="15%">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_comments as $comment): ?>
                                <tr>
                                    <td><?php echo $comment['id']; ?></td>
                                    <td>
                                        <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo truncateText(escape($comment['content']), 50); ?>
                                        </div>
                                    </td>
                                    <td><?php echo escape($comment['author_name']); ?></td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>post.php?id=<?php echo $comment['post_id']; ?>" 
                                           target="_blank"
                                           class="text-decoration-none">
                                            <?php echo truncateText(escape($comment['post_title']), 20); ?>
                                        </a>
                                    </td>
                                    <td><?php echo formatDate($comment['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo SITE_URL; ?>post.php?id=<?php echo $comment['post_id']; ?>" 
                                               target="_blank"
                                               class="btn btn-outline-primary" 
                                               title="Просмотреть статью">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="comments.php?action=delete&id=<?php echo $comment['id']; ?>" 
                                               class="btn btn-outline-danger" 
                                               title="Удалить"
                                               onclick="return confirm('Удалить комментарий?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0">Быстрые действия</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2">
                        <a href="posts.php?action=create" class="btn btn-success w-100">
                            <i class="bi bi-plus-circle me-2"></i>
                            Новая статья
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="users.php?action=create" class="btn btn-primary w-100">
                            <i class="bi bi-person-plus me-2"></i>
                            Добавить пользователя
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="posts.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-list-ul me-2"></i>
                            Все статьи
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="users.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-people me-2"></i>
                            Все пользователи
                        </a>
                    </div>
                    <div class="col-md-2">
                        <?php if ($comments_table_exists): ?>
                        <a href="comments.php" class="btn btn-outline-warning w-100">
                            <i class="bi bi-chat-dots me-2"></i>
                            Все комментарии
                        </a>
                        <?php else: ?>
                        <button class="btn btn-outline-warning w-100" disabled>
                            <i class="bi bi-chat-dots me-2"></i>
                            Комментарии
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <a href="<?php echo SITE_URL; ?>profile.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-person-circle me-2"></i>
                            Мой профиль
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0">Системная информация</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Версия PHP:</th>
                                <td><?php echo phpversion(); ?></td>
                            </tr>
                            <tr>
                                <th>Сервер:</th>
                                <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                            </tr>
                            <tr>
                                <th>База данных:</th>
                                <td>MySQL</td>
                            </tr>
                            <tr>
                                <th>Таблица комментариев:</th>
                                <td>
                                    <?php if ($comments_table_exists): ?>
                                        <span class="badge bg-success">Создана</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Отсутствует</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Администратор:</th>
                                <td><?php echo escape($_SESSION['username']); ?></td>
                            </tr>
                            <tr>
                                <th>Ваш IP:</th>
                                <td><?php echo $_SERVER['REMOTE_ADDR']; ?></td>
                            </tr>
                            <tr>
                                <th>Время сервера:</th>
                                <td><?php echo date('d.m.Y H:i:s'); ?></td>
                            </tr>
                            <tr>
                                <th>Статус системы:</th>
                                <td>
                                    <span class="badge bg-success">Работает</span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function createCommentsTable() {
    if (confirm('Создать таблицу комментариев в базе данных?')) {
        fetch('create_comments_table.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Таблица комментариев успешно создана!');
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => {
                alert('Ошибка при создании таблицы: ' + error);
            });
    }
}

function showSqlQuery() {
    const sqlDiv = document.getElementById('sqlQuery');
    sqlDiv.style.display = sqlDiv.style.display === 'none' ? 'block' : 'none';
}

<?php if (!$comments_table_exists): ?>
document.addEventListener('DOMContentLoaded', function() {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-warning alert-dismissible fade show';
    alertDiv.innerHTML = `
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Внимание!</strong> Таблица комментариев не создана. 
        <a href="#" onclick="createCommentsTable()" class="alert-link">Создать сейчас</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.admin-content').prepend(alertDiv);
});
<?php endif; ?>
</script>

<?php adminFooter(); ?>