<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    setFlashMessage('error', 'Для доступа к личному кабинету необходимо авторизоваться');
    redirect(SITE_URL . 'login.php');
}

$page_title = "Личный кабинет";
$action = $_GET['action'] ?? 'index';

require_once 'includes/header.php';

switch ($action) {
    case 'edit':
        require_once 'includes/profile/edit_profile.php';
        break;
    case 'posts':
        require_once 'includes/profile/my_posts.php';
        break;
    case 'create':
        require_once 'includes/profile/create_post.php';
        break;
    case 'edit-post':
        require_once 'includes/profile/edit_post.php';
        break;
    case 'delete-post':
        require_once 'includes/profile/delete_post.php';
        break;
    default:
        require_once 'includes/profile/dashboard.php';
        break;
}

require_once 'includes/footer.php';
?>