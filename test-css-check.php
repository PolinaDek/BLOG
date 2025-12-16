<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Тест CSS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <style>
        body { border: 5px solid red !important; }
        .test-box { 
            width: 200px; 
            height: 100px; 
            background: blue; 
            color: white;
            padding: 20px;
            margin: 20px;
        }
        .test-box { 
            background: green !important; 
            border-radius: 8px !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        }
    </style>
</head>
<body>
    <h1>Тест подключения CSS</h1>
    
    <div class="test-box">
        Если этот блок зеленый с тенями - CSS работает!
    </div>
    
    <div class="card" style="width: 300px;">
        <div class="card-header">Тест карточки</div>
        <div class="card-body">
            Проверка стилей Bootstrap и наших стилей
        </div>
    </div>
    
    <hr>
    
    <h3>Информация:</h3>
    <ul>
        <li>SITE_URL: <?php echo SITE_URL; ?></li>
        <li>Путь к CSS: <?php echo SITE_URL; ?>assets/css/style.css</li>
        <li>Полный путь на сервере: <?php echo __DIR__; ?>/assets/css/style.css</li>
        <li>Файл существует: <?php echo file_exists(__DIR__ . '/assets/css/style.css') ? 'ДА' : 'НЕТ'; ?></li>
    </ul>
    
    <a href="<?php echo SITE_URL; ?>profile.php">Перейти в личный кабинет</a>
</body>
</html>