<?php
session_start();

// --- データベース接続 ---
$dsn = 'mysql:dbname=stellasora_db;host=localhost;charset=utf8';
$user = 'root'; 
$password = '';
try { 
    $dbh = new PDO($dsn, $user, $password); 
    // エラーを表示する設定
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { 
    exit("接続失敗: " . $e->getMessage()); 
}

// --- Google API 設定 ---
define('GOOGLE_CLIENT_ID', '356825216115-rip011ujbpurh2a496u5fjcqmlmolotj.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('REDIRECT_URI', 'http://localhost/stellasora-chat/auth.php');

$error = "";

// CSRF対策：ランダムな文字列をセッションに保存
if (!isset($_SESSION['oauth_state'])) {
    $_SESSION['oauth_state'] = bin2hex(random_bytes(16));
}

// --- Googleログイン処理 (リダイレクト戻り時) ---
if (isset($_GET['code'])) {
    // stateの検証
    if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
        exit('不正なセッションです。');
    }

    $params = [
        'code' => $_GET['code'],
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    
    // アクセストークン取得
    $ch = curl_init('https://accounts.google.com/o/oauth2/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $token_res = curl_exec($ch);
    curl_close($ch);
    $token_data = json_decode($token_res, true);

    if (isset($token_data['access_token'])) {
        // ユーザー情報取得
        $info_res = file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $token_data['access_token']);
        $user_info = json_decode($info_res, true);
        
        $email = $user_info['email'];
        $name = $user_info['name'];

        // DBに存在するかチェック
        $stmt = $dbh->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user_data = $stmt->fetch();

        if (!$user_data) {
            // 未登録なら自動で新規作成
            $stmt = $dbh->prepare("INSERT INTO users (email, password, display_name) VALUES (?, 'GOOGLE_AUTH', ?)");
            $stmt->execute([$email, $name]);
            $_SESSION['user_id'] = $dbh->lastInsertId();
            $_SESSION['display_name'] = $name;
            header('Location: profile_edit.php?first_time=1');
        } else {
            // 登録済みならログイン
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['display_name'] = $user_data['display_name'] ?: $name;
            header('Location: timeline.php');
        }
        exit;
    }
}

// --- 通常ログイン処理 (フォーム送信時) ---
if (isset($_POST['login'])) {
    $stmt = $dbh->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    $u = $stmt->fetch();
    if ($u && password_verify($_POST['password'], $u['password'])) {
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['display_name'] = $u['display_name'] ?: '名無しさん';
        header('Location: timeline.php'); 
        exit;
    } else { 
        $error = "メールアドレスまたはパスワードが違います。"; 
    }
}

// ログアウト処理
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy(); 
    header('Location: auth.php'); 
    exit;
}

// Google認証URL生成
$google_url = "https://accounts.google.com/o/oauth2/auth?client_id=" . GOOGLE_CLIENT_ID . "&redirect_uri=" . REDIRECT_URI . "&scope=email%20profile&response_type=code&state=" . $_SESSION['oauth_state'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - ココチャ</title>
    <style>
        body { background: #e9eef2; font-family: "Helvetica Neue", Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .auth-box { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 320px; text-align: center; }
        
        /* Googleボタン */
        .btn-google {
            background-color: #fff;
            color: #757575;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 1px;
            cursor: pointer;
            display: flex;
            align-items: center;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 25px;
            transition: background-color .2s, box-shadow .2s;
            width: 100%;
            box-sizing: border-box;
        }
        .btn-google:hover { background-color: #fafafa; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .google-icon-wrapper { background-color: #fff; padding: 10px; display: flex; align-items: center; justify-content: center; }
        .google-icon { width: 18px !important; height: 18px !important; display: block; }
        .btn-google-text { padding-left: 10px; font-weight: 500; }

        /* 入力フォーム */
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        .btn-main { background: #007bff; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer; width: 100%; font-weight: bold; font-size: 16px; margin-top: 15px; }
        
        /* 区切り線 */
        .divider { font-size: 0.8em; color: #888; position: relative; margin-bottom: 20px; margin-top: 10px; }
        .divider::before { content: ""; position: absolute; top: 50%; left: 0; width: 100%; border-top: 1px solid #eee; z-index: 0; }
        .divider span { background: white; padding: 0 10px; position: relative; z-index: 1; }

        /* 新規登録リンク */
        .signup-link { margin-top: 25px; font-size: 0.85em; color: #666; }
        .signup-link a { color: #007bff; text-decoration: none; font-weight: bold; }
        .signup-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="auth-box">
    <h2 style="margin-top:0; margin-bottom: 25px; color:#333;">🚀 ココチャ</h2>
    
    <a href="<?php echo $google_url; ?>" class="btn-google">
        <div class="google-icon-wrapper">
            <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" class="google-icon" alt="Google">
        </div>
        <span class="btn-google-text">Googleアカウントでログイン</span>
    </a>

    <div class="divider"><span>または</span></div>

    <?php if($error): ?>
        <p style="color:#d93025; font-size:0.8em; background-color:#fce8e6; padding:10px; border-radius:4px; margin-bottom:15px;">
            <?php echo $error; ?>
        </p>
    <?php endif; ?>
    
    <form method="POST">
        <input type="email" name="email" placeholder="メールアドレス" required>
        <input type="password" name="password" placeholder="パスワード" required>
        <button type="submit" name="login" class="btn-main">ログイン</button>
    </form>

    <div class="signup-link">
        アカウントをお持ちでないですか？<br>
        <a href="register.php">新しくアカウントを作成する</a>
    </div>
</div>
</body>
</html>