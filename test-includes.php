<?php
echo "<h3>Проверка включений config.php</h3>";

// Подсчитаем, сколько раз подключается config.php
$inclusion_count = 0;

// Перехватываем require/require_once
$original_require_once = function_exists('debug_backtrace') ? true : false;

// Создаем функцию для отслеживания
function track_require($filename) {
    static $count = 0;
    $count++;
    echo "Включение #$count: $filename<br>";
    return true;
}

$files_to_check = [
    'index.php',
    'login.php', 
    'register.php',
    'profile.php',
    'post.php',
    'admin/index.php'
];

foreach ($files_to_check as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $count = substr_count($content, 'config.php');
        echo "$file: упоминаний config.php - $count<br>";
        
        if (strpos($content, 'require_once') !== false) {
            echo "  Использует require_once ✓<br>";
        } elseif (strpos($content, 'require') !== false) {
            echo "  Использует require (может вызывать дублирование!)<br>";
        }
    }
}
?>