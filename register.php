<?php
session_start();
$dsn = 'mysql:dbname=stellasora_db;host=localhost;charset=utf8';
$user = 'root'; $password = '';
try { $dbh = new PDO($dsn, $user, $password); } catch (PDOException $e) { exit($e->getMessage()); }

$error = "";

if (isset($_POST['register'])) {
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $name = $_POST['display_name'];

    // メアド重複チェック
    $stmt = $dbh->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $error = "このメールアドレスは既に登録されています。";
    } else {
        // 新規登録実行
        $stmt = $dbh->prepare("INSERT INTO users (email, password, display_name) VALUES (?, ?, ?)");
        $stmt->execute([$email, $pass, $name]);
        
        $_SESSION['user_id'] = $dbh->lastInsertId();
        $_SESSION['display_name'] = $name;
        header('Location: timeline.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>新規登録 - ココチャ</title>
    <style>
        body { background: #e9eef2; font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .auth-box { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 320px; text-align: center; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn-main { background: #28a745; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer; width: 100%; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>
<div class="auth-box">
    <h2>📝 新規登録</h2>
    <?php if($error): ?><p style="color:red; font-size:0.8em;"><?php echo $error; ?></p><?php endif; ?>
    <form method="POST">
        <input type="text" name="display_name" placeholder="名前（表示名）" required>
        <input type="email" name="email" placeholder="メールアドレス" required>
        <input type="password" name="password" placeholder="パスワード" required>
        <button type="submit" name="register" class="btn-main">登録する</button>
    </form>
    <div style="margin-top: 20px; font-size: 0.9em;">
        <a href="auth.php" style="color: #666; text-decoration: none;">← ログインに戻る</a>
    </div>
</div>
</body>
</html>