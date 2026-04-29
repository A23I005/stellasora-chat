<?php
$dsn = 'mysql:dbname=stellasora_db;host=localhost;charset=utf8';
$user = 'root';
$password = '';

try {
    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}

// --- 【追加】送信ボタンが押された時の保存処理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $char_name = $_POST['char_name'];
    $content = $_POST['content'];
    
    // 現在のメッセージ数を数えて、次の order_num を決める
    $count_sql = "SELECT COUNT(*) FROM messages";
    $count = $dbh->query($count_sql)->fetchColumn();
    $order_num = $count + 1;

    // データベースに保存
    $insert_sql = "INSERT INTO messages (char_name, content, order_num) VALUES (:char_name, :content, :order_num)";
    $stmt = $dbh->prepare($insert_sql);
    $stmt->bindValue(':char_name', $char_name, PDO::PARAM_STR);
    $stmt->bindValue(':content', $content, PDO::PARAM_STR);
    $stmt->bindValue(':order_num', $order_num, PDO::PARAM_INT);
    $stmt->execute();

    // 再読み込みして二重送信を防止
    header('Location: ./index.php');
    exit;
}

// メッセージ一覧を取得
$sql = "SELECT * FROM messages ORDER BY order_num ASC";
$stmt = $dbh->query($sql);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ココチャ・投稿機能付き</title>
    <style>
        body { background: #f0f0f0; font-family: sans-serif; padding: 20px; margin: 0; }
        #admin-form { background: white; padding: 20px; border-radius: 10px; max-width: 500px; margin: 0 auto 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        input, textarea { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        button:hover { background: #0056b3; }
        
        #chat-box { max-width: 500px; margin: 0 auto; border-top: 2px dashed #ccc; padding-top: 20px; }
        .message { background: white; padding: 15px; border-radius: 15px; margin-bottom: 10px; display: none; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .active { display: block; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; } }
        .guide { text-align: center; color: #888; margin-top: 10px; }
    </style>
</head>
<body>

<div id="admin-form">
    <h3>新しいメッセージを追加</h3>
    <form method="POST">
        <input type="text" name="char_name" placeholder="キャラクター名（例：魔王）" required>
        <textarea name="content" rows="3" placeholder="セリフを入力してください" required></textarea>
        <button type="submit">メッセージを登録する</button>
    </form>
</div>

<div id="chat-box" onclick="nextMessage()">
    <?php foreach ($messages as $msg): ?>
        <div class="message">
            <b><?php echo htmlspecialchars($msg['char_name'], ENT_QUOTES, 'UTF-8'); ?>:</b><br>
            <?php echo nl2br(htmlspecialchars($msg['content'], ENT_QUOTES, 'UTF-8')); ?>
        </div>
    <?php endforeach; ?>
    <p class="guide">※登録後、ここをタップすると再生されます</p>
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