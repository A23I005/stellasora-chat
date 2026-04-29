<?php
session_start();

// 1. データベース接続
$dsn = 'mysql:dbname=stellasora_db;host=localhost;charset=utf8';
$user = 'root'; 
$password = '';
try { 
    $dbh = new PDO($dsn, $user, $password); 
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { 
    exit("接続失敗: " . $e->getMessage()); 
}

// 2. 表示対象のユーザー情報を取得
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $dbh->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$profile_id]);
$user_data = $stmt->fetch();

if (!$user_data) {
    exit("ユーザーが見つかりません。");
}

// 3. 表示するタブの判定 (posts か likes)
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'posts';

// 4. 投稿データの取得
if ($tab === 'likes') {
    // 【いいねした投稿】thread_likesテーブルを経由してスレッドを取得
    $stmt = $dbh->prepare("
        SELECT t.*, u.display_name 
        FROM threads t 
        JOIN thread_likes l ON t.id = l.thread_id 
        JOIN users u ON t.user_id = u.id 
        WHERE l.user_id = ? 
        ORDER BY l.created_at DESC
    ");
    $stmt->execute([$profile_id]);
} else {
    // 【自身の投稿】単にthreadsからそのユーザーのものを取得
    $stmt = $dbh->prepare("SELECT t.*, u.display_name FROM threads t JOIN users u ON t.user_id = u.id WHERE t.user_id = ? ORDER BY t.created_at DESC");
    $stmt->execute([$profile_id]);
}
$display_threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

$is_mine = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profile_id);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($user_data['display_name']); ?>のプロフィール</title>
    <style>
        body { background: #e9eef2; font-family: sans-serif; margin: 0; padding-top: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        /* プロフィールヘッダー */
        .profile-header { padding: 40px 20px; text-align: center; border-bottom: 1px solid #eee; }
        .avatar { width: 80px; height: 80px; background: #9b59b6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 15px; }
        h2 { margin: 10px 0; font-size: 1.5em; }
        .email { color: #888; font-size: 0.9em; margin-bottom: 15px; }
        
        /* X風タブメニュー */
        .tabs { display: flex; border-bottom: 1px solid #eee; }
        .tab-item { flex: 1; text-align: center; padding: 15px 0; text-decoration: none; color: #666; font-weight: bold; position: relative; transition: background 0.2s; }
        .tab-item:hover { background: #f8f9fa; }
        .tab-item.active { color: #000; }
        .tab-item.active::after { content: ""; position: absolute; bottom: 0; left: 25%; width: 50%; height: 4px; background: #007bff; border-radius: 2px; }

        /* 投稿一覧 */
        .post-list { padding: 10px; background: #f8f9fa; }
        .post-card { background: white; padding: 15px; border-radius: 10px; margin-bottom: 10px; border: 1px solid #eee; }
        .post-title { display: block; font-weight: bold; color: #007bff; text-decoration: none; margin-bottom: 5px; }
        .post-meta { font-size: 0.8em; color: #888; }
        
        .empty-msg { text-align: center; padding: 40px; color: #888; }
        .btn-edit { display: inline-block; padding: 8px 16px; border: 1px solid #ddd; border-radius: 20px; text-decoration: none; color: #333; font-size: 0.9em; margin-top: 10px; }
        .back-link { display: block; text-align: center; margin: 20px 0; color: #007bff; text-decoration: none; font-size: 0.9em; }
    </style>
</head>
<body>

<div class="container">
    <div class="profile-header">
        <div class="avatar">👤</div>
        <h2><?php echo htmlspecialchars($user_data['display_name']); ?></h2>
        <p class="email"><?php echo htmlspecialchars($user_data['email']); ?></p>
        <?php if ($is_mine): ?>
            <a href="profile_edit.php" class="btn-edit">プロフィールを編集</a>
        <?php endif; ?>
    </div>

    <div class="tabs">
        <a href="user_profile.php?id=<?php echo $profile_id; ?>&tab=posts" class="tab-item <?php echo ($tab === 'posts') ? 'active' : ''; ?>">投稿</a>
        <a href="user_profile.php?id=<?php echo $profile_id; ?>&tab=likes" class="tab-item <?php echo ($tab === 'likes') ? 'active' : ''; ?>">いいね</a>
    </div>

    <div class="post-list">
        <?php if (empty($display_threads)): ?>
            <div class="empty-msg">
                <?php echo ($tab === 'likes') ? 'まだいいねした投稿はありません。' : 'まだ投稿がありません。'; ?>
            </div>
        <?php else: ?>
            <?php foreach ($display_threads as $t): ?>
                <div class="post-card">
                    <a href="index.php?thread_id=<?php echo $t['id']; ?>" class="post-title">
                        <?php echo htmlspecialchars($t['title']); ?>
                    </a>
                    <div class="post-meta">
                        投稿者: <?php echo htmlspecialchars($t['display_name']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<a href="timeline.php" class="back-link">← タイムラインに戻る</a>

</body>
</html>