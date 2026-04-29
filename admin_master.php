<?php
// --- セキュリティ設定 ---
$admin_pass = '0819Syapukuruto'; // ★ここを好きなパスワードに変えてください

session_start();
if (isset($_POST['login_pass']) && $_POST['login_pass'] === $admin_pass) {
    $_SESSION['admin_auth'] = true;
}

if (!isset($_SESSION['admin_auth'])) {
    exit('
        <form method="POST" style="text-align:center; padding:100px;">
            <h2>管理者認証</h2>
            <input type="password" name="login_pass" placeholder="パスワード">
            <button type="submit">ログイン</button>
        </form>
    ');
}

// --- データベース接続 ---
$dsn = 'mysql:dbname=stellasora_db;host=localhost;charset=utf8';
$user = 'root'; $password = '';
try { $dbh = new PDO($dsn, $user, $password); } catch (PDOException $e) { exit($e->getMessage()); }

// --- 登録処理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['icon_image'])) {
    $name = $_POST['char_name'];
    $filename = basename($_FILES['icon_image']['name']);
    $upload_path = 'icons/' . $filename;

    if (move_uploaded_file($_FILES['icon_image']['tmp_name'], $upload_path)) {
        $stmt = $dbh->prepare("INSERT INTO characters (name, icon_file) VALUES (:name, :icon)");
        $stmt->execute([':name' => $name, ':icon' => $filename]);
        echo "<p style='color:green;'>登録完了: $name</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>キャラマスター登録</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f9; display: flex; justify-content: center; padding: 50px; }
        .container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); width: 400px; }
        #drop-area { border: 2px dashed #ccc; padding: 20px; text-align: center; cursor: pointer; margin: 15px 0; border-radius: 10px; transition: 0.3s; }
        #drop-area.highlight { border-color: #007bff; background: #e7f1ff; }
        input[type="text"] { width: 100%; padding: 10px; box-sizing: border-box; margin-bottom: 10px; }
        #preview { width: 80px; height: 80px; object-fit: cover; border-radius: 50%; display: none; margin: 10px auto; }
        button { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h2>キャラ登録マスター</h2>
    <form id="char-form" method="POST" enctype="multipart/form-data">
        <input type="text" name="char_name" placeholder="キャラクター名を入力" required>
        
        <div id="drop-area">
            <p>画像をドラッグ＆ドロップ<br>またはクリックして選択</p>
            <input type="file" name="icon_image" id="fileElem" accept="image/*" style="display:none" required>
            <img id="preview">
        </div>
        
        <button type="submit">データベースに登録</button>
    </form>
    <p><a href="index.php">← メイン画面へ戻る</a></p>
</div>

<script>
    const dropArea = document.getElementById('drop-area');
    const fileElem = document.getElementById('fileElem');
    const preview = document.getElementById('preview');

    dropArea.onclick = () => fileElem.click();

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
        dropArea.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); });
    });

    dropArea.ondrop = (e) => {
        fileElem.files = e.dataTransfer.files;
        updatePreview(fileElem.files[0]);
    };

    fileElem.onchange = () => updatePreview(fileElem.files[0]);

    function updatePreview(file) {
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }
</script>
</body>
</html>