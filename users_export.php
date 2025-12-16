<?php
require_once 'includes/admin_auth.php';

$format = $_GET['format'] ?? 'csv';

try {
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.username,
            u.email,
            u.role,
            u.created_at,
            COUNT(DISTINCT p.id) as posts_count,
            COUNT(DISTINCT c.id) as comments_count
        FROM users u
        LEFT JOIN posts p ON u.id = p.author_id
        LEFT JOIN comments c ON u.id = c.user_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    
    $users = $stmt->fetchAll();
    
    if ($format === 'excel') {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="users_' . date('Y-m-d_H-i') . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo '<!DOCTYPE html>';
        echo '<html>';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; }';
        echo 'th, td { border: 1px solid #000; padding: 8px; text-align: left; }';
        echo 'th { background-color: #f2f2f2; font-weight: bold; }';
        echo '.header { background-color: #4CAF50; color: white; }';
        echo '.total { font-weight: bold; background-color: #e8f4f8; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        
        echo '<h2>Список пользователей</h2>';
        echo '<p><strong>Дата экспорта:</strong> ' . date('d.m.Y H:i:s') . '</p>';
        echo '<p><strong>Всего пользователей:</strong> ' . count($users) . '</p>';
        
        echo '<table>';
        echo '<tr class="header">';
        echo '<th>ID</th>';
        echo '<th>Имя пользователя</th>';
        echo '<th>Email</th>';
        echo '<th>Роль</th>';
        echo '<th>Дата регистрации</th>';
        echo '<th>Статей</th>';
        echo '<th>Комментариев</th>';
        echo '</tr>';
        
        $total_posts = 0;
        $total_comments = 0;
        
        foreach ($users as $user) {
            $total_posts += $user['posts_count'];
            $total_comments += $user['comments_count'];
            
            echo '<tr>';
            echo '<td>' . $user['id'] . '</td>';
            echo '<td>' . htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . ($user['role'] === 'admin' ? 'Администратор' : 'Пользователь') . '</td>';
            echo '<td>' . date('d.m.Y H:i', strtotime($user['created_at'])) . '</td>';
            echo '<td>' . $user['posts_count'] . '</td>';
            echo '<td>' . $user['comments_count'] . '</td>';
            echo '</tr>';
        }
        
        echo '<tr class="total">';
        echo '<td colspan="5"><strong>ИТОГО:</strong></td>';
        echo '<td><strong>' . $total_posts . '</strong></td>';
        echo '<td><strong>' . $total_comments . '</strong></td>';
        echo '</tr>';
        
        echo '</table>';
        
        echo '<br><br>';
        echo '<div style="font-size: 12px; color: #666;">';
        echo '<p><em>Экспортировано из системы ' . SITE_NAME . '</em></p>';
        echo '<p><em>URL: ' . SITE_URL . '</em></p>';
        echo '</div>';
        
        echo '</body></html>';
        
    } elseif ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="users_' . date('Y-m-d_H-i') . '.json"');
        header('Cache-Control: max-age=0');
        
        $export_data = [
            'meta' => [
                'export_date' => date('Y-m-d H:i:s'),
                'site_name' => SITE_NAME,
                'site_url' => SITE_URL,
                'total_users' => count($users),
                'format' => 'json'
            ],
            'users' => []
        ];
        
        foreach ($users as $user) {
            $export_data['users'][] = [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'role_display' => $user['role'] === 'admin' ? 'Администратор' : 'Пользователь',
                'created_at' => $user['created_at'],
                'created_at_formatted' => date('d.m.Y H:i', strtotime($user['created_at'])),
                'posts_count' => (int)$user['posts_count'],
                'comments_count' => (int)$user['comments_count']
            ];
        }
        
        $export_data['statistics'] = [
            'total_posts' => array_sum(array_column($export_data['users'], 'posts_count')),
            'total_comments' => array_sum(array_column($export_data['users'], 'comments_count')),
            'admin_count' => count(array_filter($export_data['users'], function($user) {
                return $user['role'] === 'admin';
            })),
            'user_count' => count(array_filter($export_data['users'], function($user) {
                return $user['role'] === 'user';
            }))
        ];
        
        echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
    } else {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="users_' . date('Y-m-d_H-i') . '.csv"');
        header('Cache-Control: max-age=0');
        
        $output = fopen('php://output', 'w');
        
        fwrite($output, "\xEF\xBB\xBF");
        
        fputcsv($output, [
            'ID',
            'Имя пользователя',
            'Email',
            'Роль',
            'Роль (отображаемая)',
            'Дата регистрации (ISO)',
            'Дата регистрации (форматированная)',
            'Количество статей',
            'Количество комментариев'
        ], ';');
        
        foreach ($users as $user) {
            fputcsv($output, [
                $user['id'],
                $user['username'],
                $user['email'],
                $user['role'],
                $user['role'] === 'admin' ? 'Администратор' : 'Пользователь',
                $user['created_at'],
                date('d.m.Y H:i', strtotime($user['created_at'])),
                $user['posts_count'],
                $user['comments_count']
            ], ';');
        }
        
        $total_posts = array_sum(array_column($users, 'posts_count'));
        $total_comments = array_sum(array_column($users, 'comments_count'));
        
        fputcsv($output, ['', '', '', '', '', '', 'ИТОГО:', $total_posts, $total_comments], ';');
        
        fclose($output);
    }
    
} catch (PDOException $e) {
    setFlashMessage('error', 'Ошибка при экспорте данных: ' . $e->getMessage());
    redirect(ADMIN_URL . 'users.php');
}

exit();
?>