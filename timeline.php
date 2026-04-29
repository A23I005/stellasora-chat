<?php
session_start();

// 1. ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

// データベース接続
$dsn = 'mysql:dbname=stellasora_db;host=localhost;charset=utf8';
$user = 'root'; 
$password = '';
try { 
    $dbh = new PDO($dsn, $user, $password); 
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { 
    exit("接続失敗: " . $e->getMessage()); 
}

// --- 削除処理 ---
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    // 自分の投稿であること（user_idの一致）を確認して削除
    $stmt = $dbh->prepare("DELETE FROM threads WHERE id = ? AND user_id = ?");
    $stmt->execute([$delete_id, $_SESSION['user_id']]);
    
    // 削除後はリダイレクトしてURLを綺麗にする
    header('Location: timeline.php');
    exit;
}

// --- 新規投稿処理 ---
if (isset($_POST['title']) && !empty($_POST['title'])) {
    $stmt = $dbh->prepare("INSERT INTO threads (user_id, title) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_POST['title']]);
    header('Location: timeline.php');
    exit;
}

// --- 投稿一覧取得 ---
$stmt = $dbh->query("SELECT t.*, u.display_name FROM threads t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC");
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>タイムライン - ココチャ</title>
    <style>
        body { background: #e9eef2; font-family: "Helvetica Neue", Arial, sans-serif; margin: 0; padding-top: 70px; }
        header { 
            background: #fff; height: 60px; padding: 0 20px; display: flex; 
            justify-content: space-between; align-items: center; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: fixed; 
            top: 0; width: 100%; box-sizing: border-box; z-index: 1000;
        }
        .logo { font-size: 1.2em; font-weight: bold; text-decoration: none; color: #333; }
        .user-nav { display: flex; align-items: center; gap: 15px; font-size: 0.9em; }
        .container { max-width: 600px; margin: 0 auto; padding: 0 15px; }
        
        /* 投稿フォーム */
        .post-form { background: #fff; padding: 20px; border-radius: 12px; margin-bottom: 20px; display: flex; gap: 10px; }
        .post-form input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .btn-submit { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }

        /* 投稿カード */
        .post-card { 
            background: white; padding: 20px; border-radius: 12px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 15px;
            position: relative;
        }
        .post-title { font-size: 1.1em; font-weight: bold; color: #333; text-decoration: none; display: block; margin-bottom: 5px; }
        .post-info { font-size: 0.85em; color: #666; display: flex; justify-content: space-between; align-items: center; }
        
        .btn-delete { 
            color: #ff4d4d; text-decoration: none; font-size: 0.85em; 
            padding: 4px 8px; border-radius: 4px; transition: background 0.2s;
        }
        .btn-delete:hover { background: #fff5f5; }
    </style>
</head>
<body>

<header>
    <a href="index.php" class="logo">🚀 ココチャ</a>
    <div class="user-nav">
        <a href="user_profile.php?id=<?php echo $_SESSION['user_id']; ?>" style="text-decoration:none; color:#333;">
            👤 <?php echo htmlspecialchars($_SESSION['display_name']); ?> さん
        </a>
        <a href="auth.php?action=logout" style="color:#888; text-decoration:none; font-size:0.8em;">ログアウト</a>
    </div>
</header>

<div class="container">
    <form class="post-form" method="POST">
        <input type="text" name="title" placeholder="タイトルを入力して投稿..." required>
        <button type="submit" class="btn-submit">投稿</button>
    </form>

    <h2 style="font-size: 1.2em; color: #555; margin-bottom: 15px;">タイムライン</h2>

    <?php if (empty($threads)): ?>
        <p style="text-align:center; color:#888;">まだ投稿がありません。</p>
    <?php endif; ?>

    <?php foreach ($threads as $t): ?>
        <div class="post-card">
            <a href="index.php?thread_id=<?php echo $t['id']; ?>" class="post-title">
                <?php echo htmlspecialchars($t['title']); ?>
            </a>
            
            <div class="post-info">
                <span>投稿者: <a href="user_profile.php?id=<?php echo $t['user_id']; ?>" style="color:#007bff; text-decoration:none;"><?php echo htmlspecialchars($t['display_name']); ?></a></span>
                
                <?php if ($t['user_id'] == $_SESSION['user_id']): ?>
                    <a href="timeline.php?delete_id=<?php echo $t['id']; ?>" 
                       class="btn-delete" 
                       onclick="return confirm('この投稿を削除してもよろしいですか？');">
                        🗑 削除
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>