<?php
if (!defined('CONFIG_LOADED')) {
    define('CONFIG_LOADED', true);
    require_once __DIR__ . '/../../includes/config.php';
}
session_start();

require_once __DIR__ . '/../../includes/config.php';

if (!isLoggedIn()) {
    setFlashMessage('error', 'Для доступа к админ-панели необходимо авторизоваться');
    redirect(SITE_URL . 'login.php');
}

if (!isAdmin()) {
    setFlashMessage('error', 'У вас нет прав для доступа к админ-панели');
    redirect(SITE_URL);
}

function adminHeader($title = '') {
    global $pdo;
    ?>
    <!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ? $title . ' | ' : ''; ?>Админ-панель | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <style>
        .admin-sidebar {
            background-color: #2c3e50;
            min-height: calc(100vh - 56px);
            color: white;
            padding: 0;
        }
        
        .admin-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-left: 4px solid transparent;
        }
        
        .admin-sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #3498db;
        }
        
        .admin-sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.15);
            border-left-color: #3498db;
        }
        
        .admin-content {
            padding: 20px;
            background-color: #f8f9fa;
            min-height: calc(100vh - 56px);
        }
        
        .admin-card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-card-admin {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-left: 4px solid #3498db;
        }
        
        .stat-card-admin h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .stat-card-admin .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #3498db;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo ADMIN_URL; ?>">
                <i class="bi bi-gear me-2"></i>
                Админ-панель
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo escape($_SESSION['username']); ?>
                </span>
                <a href="<?php echo SITE_URL; ?>" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-house-door me-1"></i>
                    На сайт
                </a>
                <a href="<?php echo SITE_URL; ?>logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i>
                    Выйти
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 admin-sidebar">
                <nav class="nav flex-column pt-3">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>">
                        <i class="bi bi-speedometer2 me-2"></i>
                        Дашборд
                    </a>
                    
                    <div class="sidebar-header px-3 py-2 text-uppercase small text-muted mt-3">
                        Управление контентом
                    </div>
                    
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'posts.php' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>posts.php">
                        <i class="bi bi-file-text me-2"></i>
                        Статьи
                    </a>
                    
                    <try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM comments");
    $comments_count = $stmt->fetch()['count'];
} catch (Exception $e) {
    $comments_count = 0;
}
?>

<a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'comments.php' ? 'active' : ''; ?>" 
   href="<?php echo ADMIN_URL; ?>comments.php">
    <i class="bi bi-chat-dots me-2"></i>
    Комментарии
    <?php if ($comments_count > 0): ?>
        <span class="badge bg-danger float-end"><?php echo $comments_count; ?></span>
    <?php else: ?>
        <span class="badge bg-secondary float-end"><?php echo $comments_count; ?></span>
    <?php endif; ?>
</a>
                    
                    <div class="sidebar-header px-3 py-2 text-uppercase small text-muted mt-3">
                        Управление пользователями
                    </div>
                    
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>users.php">
                        <i class="bi bi-people me-2"></i>
                        Пользователи
                    </a>
                    
                    <div class="sidebar-header px-3 py-2 text-uppercase small text-muted mt-3">
                        Настройки
                    </div>
                    
                    <a class="nav-link" href="#">
                        <i class="bi bi-sliders me-2"></i>
                        Настройки сайта
                    </a>
                    
                    <a class="nav-link" href="#">
                        <i class="bi bi-shield-check me-2"></i>
                        Безопасность
                    </a>
                    
                    <hr class="text-muted mx-3 my-3">
                    
                    <a class="nav-link" href="<?php echo SITE_URL; ?>profile.php">
                        <i class="bi bi-person-circle me-2"></i>
                        Мой профиль
                    </a>
                </nav>
            </div>
            
            <div class="col-md-10 admin-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1"><?php echo $title; ?></h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo ADMIN_URL; ?>">Админ-панель</a></li>
                                <li class="breadcrumb-item active"><?php echo $title; ?></li>
                            </ol>
                        </nav>
                    </div>
                    <div class="btn-group">
                        <a href="<?php echo SITE_URL; ?>" class="btn btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>
                            Посмотреть сайт
                        </a>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo $_SESSION['error_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
    <?php
}

function adminFooter() {
    ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.btn-delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Вы уверены, что хотите выполнить это действие?')) {
                        e.preventDefault();
                    }
                });
            });
            
            const currentPage = window.location.pathname;
            const navLinks = document.querySelectorAll('.admin-sidebar .nav-link');
            
            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentPage.split('/').pop()) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>
    <?php
}
?>