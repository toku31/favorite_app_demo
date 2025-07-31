<?php
session_start();

// セッション変数をすべて解除
$_SESSION = [];

// セッションクッキーの削除（オプションだが推奨）
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(
    session_name(),
    '',
    time() - 42000,
    $params["path"],
    $params["domain"],
    $params["secure"],
    $params["httponly"]
  );
}

// セッションの破棄
session_destroy();

// ログインページにリダイレクト
header('Location: login.php');
exit();
