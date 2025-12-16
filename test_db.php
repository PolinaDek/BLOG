<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS blog_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE blog_db");
    
    echo "База данных создана успешно!<br>";
    
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    CREATE TABLE IF NOT EXISTS posts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(200) NOT NULL,
        content TEXT NOT NULL,
        excerpt VARCHAR(300),
        author_id INT NOT NULL,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ";
    
    $pdo->exec($sql);
    echo "Таблицы созданы успешно!<br>";
    
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@blog.com', $password_hash, 'admin']);
    
    echo "Тестовый администратор добавлен (логин: admin, пароль: admin123)<br>";
    echo "<h3 style='color: green;'>ВСЁ ГОТОВО! Можно приступать к разработке.</h3>";
    
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>