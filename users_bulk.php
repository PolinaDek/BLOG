<?php
require_once 'includes/admin_auth.php';

$page_title = "Массовые операции с пользователями";
adminHeader($page_title);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $selected_users = $_POST['selected_users'] ?? [];
    
    if (empty($selected_users)) {
        setFlashMessage('error', 'Не выбраны пользователи');
        redirect(ADMIN_URL . 'users_bulk.php');
    }
    
    try {
        switch ($action) {
            case 'change_role':
                $new_role = $_POST['new_role'] ?? '';
                if (!in_array($new_role, ['user', 'admin'])) {
                    setFlashMessage('error', 'Некорректная роль');
                    break;
                }
                
                $ids = implode(',', array_map('intval', $selected_users));
                
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET role = ? 
                    WHERE id IN ($ids) AND id != ?
                ");
                $stmt->execute([$new_role, $_SESSION['user_id']]);
                
                $affected = $stmt->rowCount();
                setFlashMessage('success', "Роль изменена для $affected пользователей");
                break;
                
            case 'delete':
                if (in_array($_SESSION['user_id'], $selected_users)) {
                    setFlashMessage('error', 'Вы не можете удалить свой собственный аккаунт через массовые операции');
                    redirect(ADMIN_URL . 'users_bulk.php');
                }
                
                $ids = implode(',', array_map('intval', $selected_users));
                
                $stmt = $pdo->prepare("DELETE FROM posts WHERE author_id IN ($ids)");
                $stmt->execute();
                
                $stmt = $pdo->prepare("DELETE FROM comments WHERE user_id IN ($ids)");
                $stmt->execute();
                
                $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($ids)");
                $stmt->execute();
                
                $affected = $stmt->rowCount();
                setFlashMessage('success', "Удалено $affected пользователей");
                break;
                
            case 'export_selected':
                $ids = implode(',', array_map('intval', $selected_users));
                
                $stmt = $pdo->prepare("
                    SELECT u.*, 
                           COUNT(DISTINCT p.id) as posts_count,
                           COUNT(DISTINCT c.id) as comments_count
                    FROM users u
                    LEFT JOIN posts p ON u.id = p.author_id
                    LEFT JOIN comments c ON u.id = c.user_id
                    WHERE u.id IN ($ids)
                    GROUP BY u.id
                ");
                $stmt->execute();
                $selected_users_data = $stmt->fetchAll();
                
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="selected_users_' . date('Y-m-d_H-i') . '.csv"');
                
                $output = fopen('php://output', 'w');
                fwrite($output, "\xEF\xBB\xBF");
                
                fputcsv($output, ['ID', 'Имя', 'Email', 'Роль', 'Дата регистрации', 'Статей', 'Комментариев'], ';');
                
                foreach ($selected_users_data as $user) {
                    fputcsv($output, [
                        $user['id'],
                        $user['username'],
                        $user['email'],
                        $user['role'],
                        $user['created_at'],
                        $user['posts_count'],
                        $user['comments_count']
                    ], ';');
                }
                
                fclose($output);
                exit();
        }
        
        redirect(ADMIN_URL . 'users_bulk.php');
        
    } catch (PDOException $e) {
        setFlashMessage('error', 'Ошибка: ' . $e->getMessage());
    }
}

try {
    $stmt = $pdo->query("
        SELECT u.*, 
               COUNT(DISTINCT p.id) as posts_count,
               COUNT(DISTINCT c.id) as comments_count
        FROM users u
        LEFT JOIN posts p ON u.id = p.author_id
        LEFT JOIN comments c ON u.id = c.user_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();
    
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Ошибка: ' . $e->getMessage() . '</div>';
    $users = [];
}
?>

<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0">Массовые операции с пользователями</h5>
    </div>
    <div class="card-body">
        <form id="bulkForm" method="POST" action="">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="5%">
                                <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                            </th>
                            <th width="5%">ID</th>
                            <th>Имя пользователя</th>
                            <th>Email</th>
                            <th width="15%">Роль</th>
                            <th width="15%">Дата регистрации</th>
                            <th width="10%">Статей</th>
                            <th width="10%">Комментов</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="selected_users[]" 
                                       value="<?php echo $user['id']; ?>"
                                       class="user-checkbox"
                                       <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                            </td>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo escape($user['username']); ?></td>
                            <td><?php echo escape($user['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                    <?php echo $user['role'] === 'admin' ? 'Админ' : 'Пользователь'; ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($user['created_at']); ?></td>
                            <td><?php echo $user['posts_count']; ?></td>
                            <td><?php echo $user['comments_count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card mt-4">
                <div class="card-body">
                    <h6>Действия с выбранными пользователями:</h6>
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label class="form-label">Изменить роль:</label>
                            <div class="input-group">
                                <select class="form-select" name="new_role">
                                    <option value="user">Пользователь</option>
                                    <option value="admin">Администратор</option>
                                </select>
                                <button type="submit" name="action" value="change_role" 
                                        class="btn btn-warning" onclick="return validateSelection()">
                                    Применить
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Экспорт:</label>
                            <button type="submit" name="action" value="export_selected" 
                                    class="btn btn-success w-100" onclick="return validateSelection()">
                                <i class="bi bi-download me-2"></i>
                                Экспорт выбранных
                            </button>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Опасные действия:</label>
                            <button type="submit" name="action" value="delete" 
                                    class="btn btn-danger w-100" 
                                    onclick="return confirmDelete()">
                                <i class="bi bi-trash me-2"></i>
                                Удалить выбранных
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.user-checkbox:not(:disabled)');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function validateSelection() {
    const selected = document.querySelectorAll('.user-checkbox:checked');
    
    if (selected.length === 0) {
        alert('Пожалуйста, выберите хотя бы одного пользователя');
        return false;
    }
    
    return true;
}

function confirmDelete() {
    if (!validateSelection()) return false;
    
    const selected = document.querySelectorAll('.user-checkbox:checked');
    return confirm(`Вы уверены, что хотите удалить ${selected.length} пользователей? Это действие нельзя отменить.`);
}
</script>

<?php adminFooter(); ?>