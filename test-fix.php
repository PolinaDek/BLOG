<?php
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1>Тест переноса текста</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                Тест 1: Длинное слово без пробелов
            </div>
            <div class="card-body">
                <div class="test-content" style="border: 2px solid red; padding: 15px;">
                    ОченьдлинноесловобезпробеловкотороенудноперенестиОченьдлинноесловобезпробеловкотороенудноперенестиОченьдлинноесловобезпробеловкотороенудноперенести
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                Тест 2: Обычный текст
            </div>
            <div class="card-body">
                <div class="test-content" style="border: 2px solid blue; padding: 15px;">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                Тест 3: Текст из базы данных
            </div>
            <div class="card-body">
                <div class="test-content" style="border: 2px solid green; padding: 15px;">
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT content FROM posts LIMIT 1");
                        $post = $stmt->fetch();
                        if ($post) {
                            echo htmlspecialchars($post['content']);
                        } else {
                            echo "Нет статей в базе";
                        }
                    } catch (Exception $e) {
                        echo "Ошибка: " . $e->getMessage();
                    }
                    ?>
                </div>
            </div>
        </div>
        
    </div>
</div>

<style>
.test-content {
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    word-break: break-word !important;
    white-space: normal !important;
    max-width: 100% !important;
    overflow-x: hidden !important;
}

* {
    max-width: 100% !important;
    overflow-wrap: break-word !important;
}
</style>

<?php require_once 'includes/footer.php'; ?>