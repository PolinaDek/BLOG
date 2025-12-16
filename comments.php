<?php
require_once 'includes/admin_auth.php';

$action = $_GET['action'] ?? 'list';
$page_title = "Управление комментариями";

adminHeader($page_title);

try {
    switch ($action) {
        case 'delete':
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                echo '<div class="alert alert-danger">Не указан комментарий для удаления</div>';
                break;
            }
            
            $comment_id = (int)$_GET['id'];
            
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            
            setFlashMessage('success', 'Комментарий удален');
            redirect(ADMIN_URL . 'comments.php');
            break;
            
        default:
            $stmt = $pdo->query("
                SELECT c.*, u.username as author_name, p.title as post_title
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN posts p ON c.post_id = p.id
                ORDER BY c.created_at DESC
            ");
            $comments = $stmt->fetchAll();
            ?>
            
            <div class="card admin-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Все комментарии (<?php echo count($comments); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($comments)): ?>
                        <div class="text-center py-5">
                            <h5 class="text-muted">Комментариев пока нет</h5>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="5%">ID</th>
                                        <th>Комментарий</th>
                                        <th width="15%">Автор</th>
                                        <th width="20%">Статья</th>
                                        <th width="15%">Дата</th>
                                        <th width="15%">Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($comments as $comment): ?>
                                    <tr>
                                        <td><?php echo $comment['id']; ?></td>
                                        <td>
                                            <?php echo truncateText(escape($comment['content']), 50); ?>
                                        </td>
                                        <td><?php echo escape($comment['author_name']); ?></td>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>post.php?id=<?php echo $comment['post_id']; ?>">
                                                <?php echo truncateText(escape($comment['post_title']), 30); ?>
                                            </a>
                                        </td>
                                        <td><?php echo formatDate($comment['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="comments.php?action=delete&id=<?php echo $comment['id']; ?>" 
                                                   class="btn btn-outline-danger" 
                                                   title="Удалить"
                                                   onclick="return confirm('Удалить комментарий?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
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
            break;
    }
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Ошибка: ' . $e->getMessage() . '</div>';
}

adminFooter();
?>