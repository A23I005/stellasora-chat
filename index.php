<?php
// エラー表示（開発中のため有効）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// データベース接続設定
$dsn = 'mysql:dbname=stellasora_db;host=localhost;charset=utf8';
$user = 'root';
$password = '';

try {
    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}

// --- 1. キャラクターマスターリストをDBから取得 ---
$char_list_sql = "SELECT * FROM characters ORDER BY id ASC";
$char_res = $dbh->query($char_list_sql);
$characters = $char_res->fetchAll(PDO::FETCH_ASSOC);

// --- 2. 投稿処理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $char_name = $_POST['char_name'];
    
    // DBから選択されたキャラのアイコン名を検索して取得
    $icon_search = $dbh->prepare("SELECT icon_file FROM characters WHERE name = :name");
    $icon_search->execute([':name' => $char_name]);
    $found_icon = $icon_search->fetchColumn();
    
    // アイコンが見つからない場合は default.png を使用
    $char_icon = $found_icon ?: 'default.png';
    $content = $_POST['content'];
    
    // 並び順(order_num)の決定
    $count_sql = "SELECT COUNT(*) FROM messages";
    $count = $dbh->query($count_sql)->fetchColumn();
    $order_num = $count + 1;

    // messagesテーブルへ保存
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

// --- 3. 削除処理 ---
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $stmt = $dbh->prepare("DELETE FROM messages WHERE id = :id");
        $stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
        $stmt->execute();
    } elseif ($_GET['action'] === 'clear') {
        $dbh->query("TRUNCATE TABLE messages");
    }
    header('Location: ./index.php');
    exit;
}

// --- 4. 表示用のメッセージ一覧取得 ---
$sql = "SELECT * FROM messages ORDER BY order_num ASC";
$stmt = $dbh->query($sql);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ココチャ・DB連携版</title>
    <style>
        body { background: #e9eef2; font-family: "Helvetica Neue", Arial, sans-serif; margin: 0; padding-bottom: 100px; }
        
        #admin-panel { 
            background: #fff; padding: 15px; border-bottom: 2px solid #ddd; 
            position: sticky; top: 0; z-index: 100;
            display: flex; justify-content: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        select, input, button { padding: 10px; margin: 0 5px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; }
        button { background: #007bff; color: white; border: none; cursor: pointer; font-weight: bold; padding: 10px 20px; }
        
        #chat-box { max-width: 450px; margin: 0 auto; padding: 10px; min-height: 200px; }
        .message-item { display: flex; align-items: flex-start; margin-bottom: 20px; position: relative; }
        .icon { width: 52px; height: 52px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-right: 12px; flex-shrink: 0; object-fit: cover; }
        
        .bubble { 
            background: #fff; padding: 12px 16px; border-radius: 18px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); position: relative; 
            max-width: 75%; display: none;
        }
        .bubble::before { 
            content: ""; position: absolute; top: 16px; left: -8px;
            border-style: solid; border-width: 8px 8px 8px 0; border-color: transparent #fff transparent transparent;
        }
        
        .char-name { font-size: 0.75rem; color: #666; margin-bottom: 4px; font-weight: bold; }
        .content { font-size: 0.95rem; line-height: 1.5; color: #333; }
        .active { display: block; animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        
        @keyframes popIn { 
            from { opacity: 0; transform: scale(0.9) translateX(-10px); } 
            to { opacity: 1; transform: scale(1) translateX(0); } 
        }
        
        .delete-link { font-size: 18px; color: #ddd; text-decoration: none; margin-left: 12px; align-self: center; }
        .guide { text-align: center; color: #99aab5; font-weight: bold; margin: 40px 0; cursor: pointer; }
        .admin-link { position: fixed; bottom: 10px; right: 10px; font-size: 12px; color: #aaa; text-decoration: none; }
    </style>
</head>
<body>

<div id="admin-panel">
    <form method="POST">
        <select name="char_name" required>
            <option value="">キャラを選択</option>
            <?php foreach ($characters as $char): ?>
                <option value="<?php echo htmlspecialchars($char['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($char['name'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="content" placeholder="セリフを入力..." required style="width:250px;">
        
        <button type="submit">追加</button>
        <a href="?action=clear" style="color:red; font-size:12px; margin-left:10px; text-decoration:none;" onclick="return confirm('リセットしますか？')">リセット</a>
    </form>
</div>

<div id="chat-box" onclick="nextMessage()">
    <?php foreach ($messages as $msg): ?>
        <div class="message-item">
            <img src="icons/<?php echo htmlspecialchars($msg['char_icon'], ENT_QUOTES, 'UTF-8'); ?>" 
                 class="icon" 
                 onerror="this.src='https://via.placeholder.com/50?text=?';">
            
            <div class="bubble">
                <div class="char-name"><?php echo htmlspecialchars($msg['char_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="content"><?php echo nl2br(htmlspecialchars($msg['content'], ENT_QUOTES, 'UTF-8')); ?></div>
            </div>
            
            <a href="?action=delete&id=<?php echo $msg['id']; ?>" 
               class="delete-link" 
               onclick="event.stopPropagation(); return confirm('削除しますか？')">×</a>
        </div>
    <?php endforeach; ?>
    
    <div class="guide">>> TAP TO PLAY <<</div>
</div>

<a href="admin_master.php" class="admin-link">Master Admin</a>

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