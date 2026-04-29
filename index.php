<?php
// 1. データベースに接続
$dsn = 'mysql:dbname=stellasora_db;host=localhost;charset=utf8';
$user = 'root';
$password = ''; // XAMPPの初期パスワードは空です

try {
    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}

// 2. メッセージ一覧を取得
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
        /* 前回のCSSをそのまま利用 */
        body { background: #f0f0f0; font-family: sans-serif; padding: 20px; cursor: pointer; }
        .message { background: white; padding: 15px; border-radius: 15px; margin-bottom: 10px; display: none; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .active { display: block; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; } }
    </style>
</head>
<body onclick="nextMessage()">

<div id="chat-box">
    <?php foreach ($messages as $msg): ?>
        <div class="message">
            <b><?php echo htmlspecialchars($msg['char_name']); ?>:</b><br>
            <?php echo nl2br(htmlspecialchars($msg['content'])); ?>
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