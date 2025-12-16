<?php

$errors = [];
$success = false;

try {
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        setFlashMessage('error', 'Пользователь не найден');
        redirect(SITE_URL . 'profile.php');
    }
    
} catch (PDOException $e) {
    $errors[] = "Ошибка при загрузке данных пользователя: " . $e->getMessage();
    $user = ['username' => '', 'email' => ''];
}

$form_data = [
    'username' => $user['username'],
    'email' => $user['email']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $form_data['username'] = $username;
    $form_data['email'] = $email;
    
    if (empty($username)) {
        $errors[] = "Имя пользователя обязательно";
    } elseif (strlen($username) < 3) {
        $errors[] = "Имя пользователя должно содержать минимум 3 символа";
    }
    
    if (empty($email)) {
        $errors[] = "Email обязателен";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный формат email";
    }
    
    if ($username !== $user['username']) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $_SESSION['user_id']]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Имя пользователя уже занято";
            }
        } catch (PDOException $e) {
            $errors[] = "Ошибка проверки имени пользователя";
        }
    }
    
    if ($email !== $user['email']) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Email уже зарегистрирован";
            }
        } catch (PDOException $e) {
            $errors[] = "Ошибка проверки email";
        }
    }
    
    $password_changed = false;
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Для изменения пароля необходимо ввести текущий пароль";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Новый пароль должен содержать минимум 6 символов";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Новые пароли не совпадают";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user_data = $stmt->fetch();
                
                if (!$user_data || !password_verify($current_password, $user_data['password_hash'])) {
                    $errors[] = "Текущий пароль неверен";
                } else {
                    $password_changed = true;
                }
            } catch (PDOException $e) {
                $errors[] = "Ошибка проверки пароля";
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            if ($password_changed) {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, password_hash = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$username, $email, $new_password_hash, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET username = ?, email = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$username, $email, $_SESSION['user_id']]);
            }
            
            $pdo->commit();
            
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            
            $success = true;
            $message = "Профиль успешно обновлен";
            if ($password_changed) {
                $message .= " (пароль изменен)";
            }
            
            setFlashMessage('success', $message);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Ошибка при обновлении профиля: " . $e->getMessage();
        }
    }
}
?>

<div class="row">
    <div class="col-md-8">
        <h1 class="mb-4">Редактирование профиля</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <h5>Профиль успешно обновлен!</h5>
                <a href="?action=index" class="btn btn-success">Вернуться в кабинет</a>
            </div>
        <?php else: ?>
        
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5>Ошибки при обновлении профиля:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <h5 class="mb-3">Основная информация</h5>
                        
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
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">Смена пароля</h5>
                        <p class="text-muted">Заполняйте только если хотите изменить пароль</p>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Текущий пароль</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="current_password" 
                                   name="current_password">
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Новый пароль</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="new_password" 
                                   name="new_password"
                                   minlength="6">
                            <div class="form-text">Минимум 6 символов</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Подтверждение нового пароля</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="confirm_password" 
                                   name="confirm_password"
                                   minlength="6">
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <div>
                                <a href="?action=index" class="btn btn-secondary">Отмена</a>
                            </div>
                            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                        </div>
                    </form>
                </div>
            </div>
            
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Информация</h5>
            </div>
            <div class="card-body">
                <p><strong>Роль:</strong> 
                    <span class="badge bg-<?php echo $_SESSION['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                        <?php echo $_SESSION['role'] === 'admin' ? 'Администратор' : 'Пользователь'; ?>
                    </span>
                </p>
                <p><strong>ID пользователя:</strong> <?php echo $_SESSION['user_id']; ?></p>
                <p><strong>Текущий email:</strong> <?php echo escape($_SESSION['email']); ?></p>
                <p><strong>Текущий логин:</strong> <?php echo escape($_SESSION['username']); ?></p>
                
                <hr>
                
                <div class="alert alert-warning">
                    <small>
                        <strong>Внимание!</strong><br>
                        После изменения имени пользователя или email 
                        вам потребуется использовать новые данные для входа.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>