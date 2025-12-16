<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    redirect(SITE_URL);
}

$page_title = "Регистрация";
$errors = [];
$success = false;

$form_data = [
    'username' => '',
    'email' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    $form_data['username'] = $username;
    $form_data['email'] = $email;
    
    if (empty($username)) {
        $errors[] = "Имя пользователя обязательно для заполнения";
    } elseif (strlen($username) < 3) {
        $errors[] = "Имя пользователя должно содержать минимум 3 символа";
    } elseif (strlen($username) > 50) {
        $errors[] = "Имя пользователя не должно превышать 50 символов";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Имя пользователя может содержать только буквы, цифры и символ подчеркивания";
    }
    
    if (empty($email)) {
        $errors[] = "Email обязателен для заполнения";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный формат email";
    }
    
    if (empty($password)) {
        $errors[] = "Пароль обязателен для заполнения";
    } elseif (strlen($password) < 6) {
        $errors[] = "Пароль должен содержать минимум 6 символов";
    } elseif ($password !== $password_confirm) {
        $errors[] = "Пароли не совпадают";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Имя пользователя уже занято";
            }
            
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Email уже зарегистрирован";
            }
            
        } catch (PDOException $e) {
            $errors[] = "Ошибка базы данных: " . $e->getMessage();
        }
    }
    
    if (empty($errors)) {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
          
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, role) 
                VALUES (?, ?, ?, 'user')
            ");
            
            $stmt->execute([$username, $email, $password_hash]);
            
            $user_id = $pdo->lastInsertId();
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user'; 
            $_SESSION['email'] = $email;
            
            $success = true;
            
        } catch (PDOException $e) {
            $errors[] = "Ошибка при регистрации: " . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Регистрация</h4>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h5>Регистрация успешна!</h5>
                        <p>Добро пожаловать, <strong><?php echo escape($username); ?></strong>!</p>
                        <p>Теперь вы можете <a href="<?php echo SITE_URL; ?>">перейти на главную</a> или 
                        <a href="<?php echo SITE_URL; ?>profile.php">в личный кабинет</a>.</p>
                    </div>
                <?php else: ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h5>Ошибки при регистрации:</h5>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Имя пользователя *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   value="<?php echo escape($form_data['username']); ?>"
                                   required
                                   minlength="3"
                                   maxlength="50">
                            <div class="form-text">От 3 до 50 символов (только буквы, цифры, _)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo escape($form_data['email']); ?>"
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль *</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   required
                                   minlength="6">
                            <div class="form-text">Минимум 6 символов</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Подтверждение пароля *</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirm" 
                                   name="password_confirm" 
                                   required
                                   minlength="6">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
                            <a href="<?php echo SITE_URL; ?>login.php" class="btn btn-link">Уже есть аккаунт? Войти</a>
                        </div>
                    </form>
                    
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-3 text-center">
            <p><small>Все новые пользователи регистрируются с ролью "Обычный пользователь".<br>
            Права администратора может назначить только существующий администратор.</small></p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>