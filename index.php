<?php
$dsn = 'mysql:dbname=stellasora_db;host=localhost;charset=utf8';
$user = 'root';
$password = '';

try {
    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}

// --- 保存処理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $char_name = $_POST['char_name'];
    $content = $_POST['content'];
    $count_sql = "SELECT COUNT(*) FROM messages";
    $count = $dbh->query($count_sql)->fetchColumn();
    $order_num = $count + 1;

    $insert_sql = "INSERT INTO messages (char_name, content, order_num) VALUES (:char_name, :content, :order_num)";
    $stmt = $dbh->prepare($insert_sql);
    $stmt->bindValue(':char_name', $char_name, PDO::PARAM_STR);
    $stmt->bindValue(':content', $content, PDO::PARAM_STR);
    $stmt->bindValue(':order_num', $order_num, PDO::PARAM_INT);
    $stmt->execute();

    header('Location: ./index.php');
    exit;
}

// --- 【追加】削除処理 ---
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        // 個別削除
        $delete_sql = "DELETE FROM messages WHERE id = :id";
        $stmt = $dbh->prepare($delete_sql);
        $stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
        $stmt->execute();
    } elseif ($_GET['action'] === 'clear') {
        // 全削除
        $dbh->query("TRUNCATE TABLE messages");
    }
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
    <title>ココチャ・管理機能付き</title>
    <style>
        body { background: #f0f0f0; font-family: sans-serif; padding: 20px; margin: 0; }
        #admin-panel { background: white; padding: 20px; border-radius: 10px; max-width: 500px; margin: 0 auto 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        input, textarea { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .btn-group { display: flex; gap: 10px; }
        button { flex: 3; padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-clear { flex: 1; background: #dc3545; font-size: 0.8em; }
        
        #chat-box { max-width: 500px; margin: 0 auto; border-top: 2px dashed #ccc; padding-top: 20px; }
        .message-item { position: relative; margin-bottom: 10px; }
        .message { background: white; padding: 15px; border-radius: 15px; display: none; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .active { display: block; animation: fadeIn 0.3s; }
        
        /* 削除ボタンのスタイル */
        .delete-link { position: absolute; top: 10px; right: -60px; color: #dc3545; text-decoration: none; font-size: 0.8em; background: white; padding: 5px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; } }
        .guide { text-align: center; color: #888; margin-top: 10px; cursor: pointer; }
    </style>
</head>
<body>

<div id="admin-panel">
    <h3>管理用フォーム</h3>
    <form method="POST">
        <input type="text" name="char_name" placeholder="キャラクター名" required>
        <textarea name="content" rows="3" placeholder="セリフ" required></textarea>
        <div class="btn-group">
            <button type="submit">登録</button>
            <a href="?action=clear" class="btn-clear" style="text-decoration:none; color:white; padding:10px; border-radius:5px; text-align:center;" onclick="return confirm('全てのメッセージを消去しますか？')">全消去</a>
        </div>
    </form>
</div>

<div id="chat-box">
    <?php foreach ($messages as $msg): ?>
        <div class="message-item">
            <div class="message">
                <b><?php echo htmlspecialchars($msg['char_name'], ENT_QUOTES, 'UTF-8'); ?>:</b><br>
                <?php echo nl2br(htmlspecialchars($msg['content'], ENT_QUOTES, 'UTF-8')); ?>
            </div>
            <a href="?action=delete&id=<?php echo $msg['id']; ?>" class="delete-link" onclick="return confirm('このメッセージを削除しますか？')">削除</a>
        </div>
    <?php endforeach; ?>
    <p class="guide" onclick="nextMessage()">【ここをタップして再生】</p>
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