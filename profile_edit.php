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
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { exit($e->getMessage()); }

// 更新処理
if (isset($_POST['update'])) {
    $new_name = $_POST['display_name'];
    $stmt = $dbh->prepare("UPDATE users SET display_name = ? WHERE id = ?");
    $stmt->execute([$new_name, $_SESSION['user_id']]);
    $_SESSION['display_name'] = $new_name;
    
    // 保存後は自分のプロフィール詳細画面に戻る
    header('Location: user_profile.php?id=' . $_SESSION['user_id']);
    exit;
}

$stmt = $dbh->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_data = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>プロフィール編集</title>
    <style>
        body { background: #e9eef2; font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .edit-box { background: white; padding: 40px; border-radius: 20px; width: 320px; text-align: center; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn-save { background: #28a745; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer; width: 100%; font-weight: bold; }
        .cancel-link { display: block; margin-top: 15px; color: #666; text-decoration: none; font-size: 0.9em; }
    </style>
</head>
<body>

<div class="edit-box">
    <h2>👤 プロフィール編集</h2>
    <form method="POST">
        <p style="font-size: 0.8em; color: #888; text-align: left; margin-bottom: 5px;">表示名</p>
        <input type="text" name="display_name" value="<?php echo htmlspecialchars($user_data['display_name']); ?>" required>
        <button type="submit" name="update" class="btn-save">変更を保存する</button>
    </form>
    <a href="user_profile.php?id=<?php echo $_SESSION['user_id']; ?>" class="cancel-link">キャンセル</a>
</div>

</body>
</html>