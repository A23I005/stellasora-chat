<?php
// エラーがあれば画面に出す設定
ini_set('display_errors', 1);
error_reporting(E_ALL);

$dsn = 'mysql:dbname=stellasora_db;host=localhost;charset=utf8';
$user = 'root';
$password = '';

try {
    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}

$sql = "SELECT * FROM messages ORDER BY order_num ASC";
$stmt = $dbh->query($sql);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ココチャ・DB連携版</title>
    <style>
        body { background: #f0f0f0; font-family: sans-serif; padding: 20px; cursor: pointer; min-height: 100vh; margin: 0; }
        #chat-box { max-width: 500px; margin: 0 auto; }
        .message { background: white; padding: 15px; border-radius: 15px; margin-bottom: 10px; display: none; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .active { display: block; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; } }
    </style>
</head>
<body onclick="nextMessage()">

<div id="chat-box">
    <?php foreach ($messages as $msg): ?>
        <div class="message">
            <b><?php echo htmlspecialchars($msg['char_name'], ENT_QUOTES, 'UTF-8'); ?>:</b><br>
            <?php echo nl2br(htmlspecialchars($msg['content'], ENT_QUOTES, 'UTF-8')); ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
    let currentIdx = 0;
    const messages = document.querySelectorAll('.message');
    function nextMessage() {
        if (currentIdx < messages.length) {
            messages[currentIdx].classList.add('active');
            currentIdx++;
            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        }
    }
</script>
</body>
</html>