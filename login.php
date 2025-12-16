<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    redirect(SITE_URL);
}

$page_title = "Вход в систему";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $errors[] = "Все поля обязательны для заполнения";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                SELECT id, username, email, password_hash, role 
                FROM users 
                WHERE username = ? OR email = ?
            ");
            $stmt->execute([$login, $login]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role']; 
                
                if ($user['role'] === 'admin') {
                    redirect(ADMIN_URL);
                } else {
                    redirect(SITE_URL);
                }
                
            } else {
                $errors[] = "Неверное имя пользователя/email или пароль";
            }
            
        } catch (PDOException $e) {
            $errors[] = "Ошибка базы данных: " . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Вход в систему</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h5>Ошибки при входе:</h5>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="login" class="form-label">Имя пользователя или Email *</label>
                        <input type="text" 
                               class="form-control" 
                               id="login" 
                               name="login" 
                               value="<?php echo isset($_POST['login']) ? escape($_POST['login']) : ''; ?>"
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Пароль *</label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Войти</button>
                        <a href="<?php echo SITE_URL; ?>register.php" class="btn btn-link">Нет аккаунта? Зарегистрироваться</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="mt-3 text-center">
            <p><small>Для входа в админ-панель необходима роль <strong>администратора</strong>.<br>
            Тестовый администратор: admin / admin123</small></p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>