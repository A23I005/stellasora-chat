<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header('Location: auth.php'); 
    exit; 
}

$dsn = 'mysql:dbname=stellasora_db;host=localhost;charset=utf8';
$user = 'root'; $password = '';
try { 
    $dbh = new PDO($dsn, $user, $password); 
} catch (PDOException $e) { 
    exit($e->getMessage()); 
}

$thread_id = isset($_GET['thread_id']) ? (int)$_GET['thread_id'] : 0;
if ($thread_id === 0) { header('Location: timeline.php'); exit; }

// --- 1. いいね処理 ---
// --- 修正版：いいね処理 (index.php) ---
if (isset($_POST['action']) && $_POST['action'] === 'like') {
    try {
        // 1. すでにいいねしているかチェック（二重いいね防止）
        $check = $dbh->prepare("SELECT COUNT(*) FROM thread_likes WHERE user_id = ? AND thread_id = ?");
        $check->execute([$_SESSION['user_id'], $thread_id]);

        if ($check->fetchColumn() == 0) {
            // 2. まだしていなければ、thread_likesテーブルに記録を追加
            $stmt = $dbh->prepare("INSERT INTO thread_likes (user_id, thread_id) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $thread_id]);

            // 3. 既存の threads テーブルの likes 数もカウントアップ（表示用）
            $stmt = $dbh->prepare("UPDATE threads SET likes = likes + 1 WHERE id = :id");
            $stmt->execute([':id' => $thread_id]);
        } else {
            // すでにいいね済みの場合は、解除（DELETE）する処理にしてもOKです
        }

        header("Location: index.php?thread_id=$thread_id"); 
        exit;
    } catch (PDOException $e) {
        // エラーハンドリング
    }
}

// --- 2. コメント投稿処理 ---
if (isset($_POST['comment_text']) && !empty($_POST['comment_text'])) {
    $stmt = $dbh->prepare("INSERT INTO comments (thread_id, user_id, comment_text) VALUES (:tid, :uid, :txt)");
    $stmt->execute([':tid' => $thread_id, ':uid' => $_SESSION['user_id'], ':txt' => $_POST['comment_text']]);
    header("Location: index.php?thread_id=$thread_id");
    exit;
}

// --- 3. セリフ投稿処理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $char_name = $_POST['char_name'];
    $icon_search = $dbh->prepare("SELECT icon_file FROM characters WHERE name = :name");
    $icon_search->execute([':name' => $char_name]);
    $char_icon = $icon_search->fetchColumn() ?: 'default.png';
    
    $stmt = $dbh->prepare("SELECT COUNT(*) FROM messages WHERE thread_id = :tid");
    $stmt->execute([':tid' => $thread_id]);
    $order_num = $stmt->fetchColumn() + 1;

    $stmt = $dbh->prepare("INSERT INTO messages (thread_id, char_name, char_icon, content, order_num) VALUES (:tid, :name, :icon, :content, :order)");
    $stmt->execute([':tid' => $thread_id, ':name' => $char_name, ':icon' => $char_icon, ':content' => $_POST['content'], ':order' => $order_num]);
    header("Location: index.php?thread_id=$thread_id");
    exit;
}

// --- 4. データ取得 (display_name を使用するように修正) ---
// スレッド詳細
$stmt = $dbh->prepare("SELECT t.*, u.display_name FROM threads t JOIN users u ON t.user_id = u.id WHERE t.id = :id");
$stmt->execute([':id' => $thread_id]);
$thread = $stmt->fetch(PDO::FETCH_ASSOC);

// セリフ一覧
$stmt = $dbh->prepare("SELECT * FROM messages WHERE thread_id = :tid ORDER BY order_num ASC");
$stmt->execute([':tid' => $thread_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// コメント一覧
$stmt = $dbh->prepare("SELECT c.*, u.display_name FROM comments c JOIN users u ON c.user_id = u.id WHERE c.thread_id = :tid ORDER BY c.created_at DESC");
$stmt->execute([':tid' => $thread_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$characters = $dbh->query("SELECT * FROM characters")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($thread['title']); ?></title>
    <style>
        body { background: #e9eef2; font-family: sans-serif; margin: 0; padding-bottom: 100px; }
        #admin-panel { background: #fff; padding: 15px; border-bottom: 1px solid #ddd; position: sticky; top: 0; z-index: 100; display: flex; justify-content: center; gap: 10px; }
        #chat-box { max-width: 450px; margin: 0 auto; padding: 10px; min-height: 300px; }
        .message-item { display: flex; align-items: flex-start; margin-bottom: 20px; }
        .icon { width: 52px; height: 52px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-right: 12px; object-fit: cover; }
        .bubble { background: #fff; padding: 12px 16px; border-radius: 18px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: none; max-width: 75%; position: relative; }
        .bubble::before { content: ""; position: absolute; top: 15px; left: -8px; border-style: solid; border-width: 8px 8px 8px 0; border-color: transparent #fff transparent transparent; }
        .active { display: block; animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        @keyframes popIn { from { opacity: 0; transform: scale(0.9) translateX(-10px); } to { opacity: 1; transform: scale(1) translateX(0); } }
        .sns-section { max-width: 450px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 12px; }
        .comment-item { border-bottom: 1px solid #eee; padding: 10px 0; font-size: 0.9em; }
        .back-link { position: fixed; top: 15px; left: 15px; z-index: 101; text-decoration: none; color: #007bff; font-weight: bold; background: rgba(255,255,255,0.8); padding: 5px 10px; border-radius: 20px; }
    </style>
</head>
<body>

<a href="timeline.php" class="back-link">← 戻る</a>

<?php if ($thread['user_id'] == $_SESSION['user_id']): ?>
<div id="admin-panel">
    <form method="POST">
        <select name="char_name" required>
            <?php foreach ($characters as $char): ?>
                <option value="<?php echo htmlspecialchars($char['name']); ?>"><?php echo htmlspecialchars($char['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="content" placeholder="セリフを入力..." required style="width:200px; padding:8px; border:1px solid #ddd; border-radius:4px;">
        <button type="submit" style="background:#007bff; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;">追加</button>
    </form>
</div>
<?php endif; ?>

<div id="chat-box" onclick="nextMessage()">
    <h2 style="text-align:center; margin-bottom:5px;"><?php echo htmlspecialchars($thread['title']); ?></h2>
    <p style="text-align:center; font-size:0.8em; color:#888; margin-bottom:30px;">by <?php echo htmlspecialchars($thread['display_name']); ?></p>
    
    <?php foreach ($messages as $msg): ?>
        <div class="message-item">
            <img src="icons/<?php echo htmlspecialchars($msg['char_icon']); ?>" class="icon" onerror="this.src='https://via.placeholder.com/50';">
            <div class="bubble">
                <div style="font-size:0.7em; color:#888; margin-bottom:4px; font-weight:bold;"><?php echo htmlspecialchars($msg['char_name']); ?></div>
                <div><?php echo nl2br(htmlspecialchars($msg['content'])); ?></div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <div style="text-align:center; color:#aaa; margin-top:40px; font-weight:bold; cursor:pointer;">>> TAP TO PLAY <<</div>
</div>

<div class="sns-section">
    <form method="POST" style="margin-bottom: 15px;">
        <input type="hidden" name="action" value="like">
        <button type="submit" style="background:#ff4757; color:#fff; border:none; padding:10px 20px; border-radius:20px; cursor:pointer; font-weight:bold;">❤ いいね！ (<?php echo $thread['likes']; ?>)</button>
    </form>
    <hr style="border:0; border-top:1px solid #eee;">
    <h4>コメント</h4>
    <form method="POST" style="margin-bottom:20px;">
        <input type="text" name="comment_text" placeholder="感想を書く..." style="width:75%; padding:10px; border:1px solid #ddd; border-radius:6px;" required>
        <button type="submit" style="padding:10px; border:none; background:#eee; border-radius:6px; cursor:pointer;">送信</button>
    </form>
    <div>
        <?php foreach ($comments as $c): ?>
            <div class="comment-item">
                <strong><?php echo htmlspecialchars($c['display_name'] ?: '名無しさん'); ?></strong>: 
                <?php echo htmlspecialchars($c['comment_text']); ?>
                <div style="font-size:0.7em; color:#ccc; margin-top:4px;"><?php echo $c['created_at']; ?></div>
            </div>
        <?php endforeach; ?>
    </div>
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