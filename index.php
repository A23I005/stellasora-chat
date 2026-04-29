<?php
// エラー表示用
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

// --- 1. 投稿処理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $char_name = $_POST['char_name'];
    $char_icon = !empty($_POST['char_icon']) ? $_POST['char_icon'] : 'default.png';
    $content = $_POST['content'];
    
    $count_sql = "SELECT COUNT(*) FROM messages";
    $count = $dbh->query($count_sql)->fetchColumn();
    $order_num = $count + 1;

    $insert_sql = "INSERT INTO messages (char_name, char_icon, content, order_num) VALUES (:char_name, :char_icon, :content, :order_num)";
    $stmt = $dbh->prepare($insert_sql);
    $stmt->bindValue(':char_name', $char_name, PDO::PARAM_STR);
    $stmt->bindValue(':char_icon', $char_icon, PDO::PARAM_STR);
    $stmt->bindValue(':content', $content, PDO::PARAM_STR);
    $stmt->bindValue(':order_num', $order_num, PDO::PARAM_INT);
    $stmt->execute();

    header('Location: ./index.php');
    exit;
}

// --- 2. 削除処理 ---
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $delete_sql = "DELETE FROM messages WHERE id = :id";
        $stmt = $dbh->prepare($delete_sql);
        $stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
        $stmt->execute();
    } elseif ($_GET['action'] === 'clear') {
        $dbh->query("TRUNCATE TABLE messages");
    }
    header('Location: ./index.php');
    exit;
}

// --- 3. メッセージ一覧の取得（ここが抜けていました！） ---
$sql = "SELECT * FROM messages ORDER BY order_num ASC";
$stmt = $dbh->query($sql);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ココチャ・カスタムエディション</title>
    <style>
        body { background: #e9eef2; font-family: "Helvetica Neue", Arial, sans-serif; margin: 0; padding-bottom: 100px; }
        #admin-panel { background: #fff; padding: 15px; border-bottom: 2px solid #ddd; margin-bottom: 20px; }
        #chat-box { max-width: 450px; margin: 0 auto; padding: 10px; }
        .message-item { display: flex; align-items: flex-start; margin-bottom: 20px; position: relative; }
        .icon { width: 50px; height: 50px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-right: 12px; flex-shrink: 0; object-fit: cover; }
        .bubble { 
            background: #fff; padding: 12px 16px; border-radius: 18px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); position: relative; 
            max-width: 70%; display: none;
        }
        .bubble::before {
            content: ""; position: absolute; top: 15px; left: -8px;
            border-style: solid; border-width: 8px 8px 8px 0; border-color: transparent #fff transparent transparent;
        }
        .char-name { font-size: 0.75rem; color: #666; margin-bottom: 4px; font-weight: bold; }
        .content { font-size: 0.95rem; line-height: 1.4; color: #333; }
        .active { display: block; animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        @keyframes popIn { from { opacity: 0; transform: scale(0.9) translateX(-10px); } to { opacity: 1; transform: scale(1) translateX(0); } }
        .delete-link { font-size: 14px; color: #ccc; text-decoration: none; margin-left: 10px; align-self: center; }
        .guide { text-align: center; color: #99aab5; font-weight: bold; margin: 40px 0; cursor: pointer; letter-spacing: 1px; }
    </style>
</head>
<body>

<div id="admin-panel">
    <form method="POST">
        <input type="text" name="char_name" placeholder="名前" required style="width:80px;">
        <input type="text" name="char_icon" placeholder="画像名(maou.png等)" style="width:120px;">
        <input type="text" name="content" placeholder="セリフを入力..." required style="width:200px;">
        <button type="submit">追加</button>
        <a href="?action=clear" onclick="return confirm('全削除しますか？')" style="font-size:12px; color:red; margin-left:10px; text-decoration:none;">リセット</a>
    </form>
</div>

<div id="chat-box" onclick="nextMessage()">
    <?php foreach ($messages as $msg): ?>
        <div class="message-item">
            <img src="icons/<?php echo htmlspecialchars($msg['char_icon'], ENT_QUOTES, 'UTF-8'); ?>" class="icon" onerror="this.src='https://via.placeholder.com/50';">
            <div class="bubble">
                <div class="char-name"><?php echo htmlspecialchars($msg['char_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="content"><?php echo nl2br(htmlspecialchars($msg['content'], ENT_QUOTES, 'UTF-8')); ?></div>
            </div>
            <a href="?action=delete&id=<?php echo $msg['id']; ?>" class="delete-link" onclick="event.stopPropagation(); return confirm('削除しますか？')">×</a>
        </div>
    <?php endforeach; ?>
    
    <div class="guide">>> TAP TO PLAY <<</div>
</div>

<script>
    let currentIdx = 0;
    const bubbles = document.querySelectorAll('.bubble');
    function nextMessage() {
        if (currentIdx < bubbles.length) {
            bubbles[currentIdx].classList.add('active');
            currentIdx++;
            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        }
    }
</script>
</body>
</html>