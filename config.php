<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (defined('BLOG_CONFIG_LOADED')) {
    return; 
}

define('BLOG_CONFIG_LOADED', true);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'blog_db');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

if (!defined('SITE_NAME')) define('SITE_NAME', 'Мой Блог');
if (!defined('SITE_URL')) define('SITE_URL', 'http://localhost/blog/');
if (!defined('ADMIN_URL')) define('ADMIN_URL', 'http://localhost/blog/admin/');


if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', dirname(__DIR__) . '/assets/uploads/');
if (!defined('UPLOAD_MAX_SIZE')) define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
if (!defined('ALLOWED_TYPES')) define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);


if (!isset($GLOBALS['blog_pdo_connection'])) {
    try {
        $GLOBALS['blog_pdo_connection'] = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    } catch (PDOException $e) {
        die("Ошибка подключения к базе данных: " . $e->getMessage());
    }
}


$pdo = &$GLOBALS['blog_pdo_connection'];


function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}


function setFlashMessage($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

function getFlashMessage($type) {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $message;
    }
    return null;
}


function redirect($url) {
   
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    
    header("Location: " . $url);
    exit();
}

function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function formatDate($date_string, $format = 'd.m.Y H:i') {
    $timestamp = strtotime($date_string);
    return $timestamp ? date($format, $timestamp) : $date_string;
}

function truncateText($text, $length = 200, $suffix = '...') {
    $text = strip_tags($text);
    if (mb_strlen($text) > $length) {
        $text = mb_substr($text, 0, $length) . $suffix;
    }
    return $text;
}

function validateUploadedFile($file) {
    $result = [
        'success' => false,
        'error' => '',
        'filename' => ''
    ];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Файл превышает максимальный размер',
            UPLOAD_ERR_FORM_SIZE => 'Файл превышает максимальный размер формы',
            UPLOAD_ERR_PARTIAL => 'Файл был загружен только частично',
            UPLOAD_ERR_NO_FILE => 'Файл не был загружен',
            UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
            UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск',
            UPLOAD_ERR_EXTENSION => 'Расширение PHP остановило загрузку файла'
        ];
        $result['error'] = $errors[$file['error']] ?? 'Неизвестная ошибка загрузки';
        return $result;
    }
    
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        $result['error'] = 'Файл слишком большой. Максимальный размер: ' . (UPLOAD_MAX_SIZE / 1024 / 1024) . 'MB';
        return $result;
    }
    
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowed_extensions)) {
        $result['error'] = 'Недопустимое расширение файла. Разрешены: ' . implode(', ', $allowed_extensions);
        return $result;
    }
    
  
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($file['tmp_name']);
    
    if (!in_array($file_type, $allowed_mimes)) {
        $result['error'] = 'Недопустимый тип файла. Разрешены только изображения.';
        return $result;
    }
 
    $filename = uniqid('img_', true) . '.' . $extension;
    
    $result['success'] = true;
    $result['filename'] = $filename;
    
    return $result;
}
?>