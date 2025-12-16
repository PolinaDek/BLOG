<?php
require_once 'includes/admin_auth.php';

$action = $_GET['action'] ?? 'list';
$page_title = "Управление пользователями";

switch ($action) {
    case 'create':
        $page_title = "Добавление пользователя";
        break;
    case 'edit':
        $page_title = "Редактирование пользователя";
        break;
    case 'delete':
        $page_title = "Удаление пользователя";
        break;
    default:
        $page_title = "Все пользователи";
}

adminHeader($page_title);

switch ($action) {
    case 'create':
        ?>
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0">Добавление нового пользователя</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="users.php?action=save">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Имя пользователя *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">Роль *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="user">Пользователь</option>
                                    <option value="admin">Администратор</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="users.php" class="btn btn-secondary">Отмена</a>
                        <button type="submit" class="btn btn-primary">Добавить пользователя</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        break;
        
    case 'edit':
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            echo '<div class="alert alert-danger">Не указан пользователь для редактирования</div>';
            echo '<a href="users.php" class="btn btn-secondary">Назад</a>';
            break;
        }
        
        $user_id = (int)$_GET['id'];
        
        try {
            $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                echo '<div class="alert alert-danger">Пользователь не найден</div>';
                echo '<a href="users.php" class="btn btn-secondary">Назад</a>';
                break;
            }
            ?>
            <div class="card admin-card">
                <div class="card-header">
                    <h5 class="mb-0">Редактирование пользователя</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="users.php?action=update&id=<?php echo $user_id; ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Имя пользователя *</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo escape($user['username']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo escape($user['email']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Роль *</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>Пользователь</option>
                                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Администратор</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Новый пароль</label>
                                    <input type="password" class="form-control" id="password" name="password">
                                    <div class="form-text">Оставьте пустым, если не хотите менять пароль</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="users.php" class="btn btn-secondary">Отмена</a>
                            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php
            
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Ошибка при загрузке пользователя: ' . $e->getMessage() . '</div>';
        }
        break;
        
    case 'delete':
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            echo '<div class="alert alert-danger">Не указан пользователь для удаления</div>';
            echo '<a href="users.php" class="btn btn-secondary">Назад</a>';
            break;
        }
        
        $user_id = (int)$_GET['id'];
        
        if ($user_id == $_SESSION['user_id']) {
            echo '<div class="alert alert-danger">Вы не можете удалить свой собственный аккаунт!</div>';
            echo '<a href="users.php" class="btn btn-secondary">Назад</a>';
            break;
        }
        
        if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) as post_count FROM posts WHERE author_id = ?");
                $stmt->execute([$user_id]);
                $post_count = $stmt->fetch()['post_count'];
                
                if ($post_count > 0) {
                    $stmt = $pdo->prepare("DELETE FROM posts WHERE author_id = ?");
                    $stmt->execute([$user_id]);
                }
                
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                setFlashMessage('success', 'Пользователь успешно удален');
                redirect(ADMIN_URL . 'users.php');
                
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Ошибка при удалении пользователя: ' . $e->getMessage() . '</div>';
            }
        } else {
            ?>
            <div class="card admin-card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Подтверждение удаления</h5>
                </div>
                <div class="card-body text-center">
                    <div class="alert alert-warning">
                        <h5>Вы уверены, что хотите удалить этого пользователя?</h5>
                        <p>Это действие удалит также все статьи пользователя.</p>
                        <p>Это действие нельзя отменить.</p>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="users.php?action=delete&id=<?php echo $user_id; ?>&confirm=yes" 
                           class="btn btn-danger">
                            Да, удалить
                        </a>
                        <a href="users.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </div>
            </div>
            <?php
        }
        break;
        
    default:
        try {
            $stmt = $pdo->query("
                SELECT id, username, email, role, created_at 
                FROM users 
                ORDER BY created_at DESC
            ");
            $users = $stmt->fetchAll();
            
            ?>
            
            <div class="card admin-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Экспорт данных</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="users_export.php" class="btn btn-success w-100">
                                <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                                Экспорт в CSV
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="users_export.php?format=excel" class="btn btn-primary w-100">
                                <i class="bi bi-file-excel me-2"></i>
                                Экспорт в Excel
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="users_export.php?format=json" class="btn btn-warning w-100">
                                <i class="bi bi-file-code me-2"></i>
                                Экспорт в JSON
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="users_bulk.php" class="btn btn-info w-100">
                                <i class="bi bi-gear me-2"></i>
                                Массовые операции
                            </a>
                        </div>
                    </div>
                    <div class="mt-3 text-muted small">
                        <i class="bi bi-info-circle me-1"></i>
                        CSV и Excel файлы можно открыть в Microsoft Excel, Google Таблицах
                    </div>
                </div>
            </div>
            
            <div class="card admin-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Все пользователи (<?php echo count($users); ?>)</h5>
                    <a href="users.php?action=create" class="btn btn-primary">
                        <i class="bi bi-person-plus me-2"></i>
                        Добавить пользователя
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <div class="text-center py-5">
                            <h5 class="text-muted">Пользователи не найдены</h5>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="5%">ID</th>
                                        <th>Имя пользователя</th>
                                        <th>Email</th>
                                        <th width="15%">Роль</th>
                                        <th width="15%">Дата регистрации</th>
                                        <th width="20%">Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td>
                                            <?php echo escape($user['username']); ?>
                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                <span class="badge bg-info ms-1">Вы</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo escape($user['email']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                                <?php echo $user['role'] === 'admin' ? 'Администратор' : 'Пользователь'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($user['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-outline-warning" 
                                                   title="Редактировать">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-outline-danger" 
                                                   title="Удалить">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <?php else: ?>
                                                <span class="text-muted small">Текущий пользователь</span>
                                                <?php endif; ?>
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
            <?php
            
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Ошибка при загрузке пользователей: ' . $e->getMessage() . '</div>';
        }
        break;
}

adminFooter();
?>