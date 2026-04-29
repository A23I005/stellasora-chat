<?php
session_start();

// ログインしていない場合は拒否
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$thread_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($thread_id === 0) {
    header('Location: timeline.php');
    exit;
}

// データベース接続
$dsn = 'mysql:dbname=stellasora_db;host=localhost;charset=utf8';
$user = 'root'; $password = '';
try { 
    $dbh = new PDO($dsn, $user, $password); 
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 削除処理：IDが一致し、かつ投稿者が自分であること
    $stmt = $dbh->prepare("DELETE FROM threads WHERE id = ? AND user_id = ?");
    $stmt->execute([$thread_id, $_SESSION['user_id']]);

    // 関連するメッセージやコメントも削除したい場合は、ここで追加のDELETE文を実行するか、
    // DBの外部キー制約(ON DELETE CASCADE)を設定しておくと楽です。

    header('Location: timeline.php');
    exit;

} catch (PDOException $e) {
    exit("削除に失敗しました: " . $e->getMessage());
}