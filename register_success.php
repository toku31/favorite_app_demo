<?php
session_start();
if (empty($_SESSION['registered'])) {
  // 不正アクセスは登録フォームにリダイレクト
  header("Location: register.php");
  exit;
}

// フラグを消す（1回しか表示されないように）
unset($_SESSION['registered']);
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <title>登録完了</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Helvetica Neue', sans-serif;
      background-color: #f4f4f4;
      margin: 0;
      padding: 40px;
    }

    .container {
      background: white;
      max-width: 400px;
      margin: 80px auto;
      padding: 30px 24px;
      border-radius: 10px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      text-align: center;
    }

    h2 {
      color: #2ecc71;
      margin-bottom: 16px;
      font-size: 1.6em;
    }

    p {
      margin-bottom: 24px;
      font-size: 1em;
      color: #555;
    }

    a.btn {
      display: inline-block;
      padding: 10px 20px;
      background-color: #3498db;
      color: white;
      border-radius: 6px;
      text-decoration: none;
      font-size: 1em;
      transition: background-color 0.2s ease;
    }

    a.btn:hover {
      background-color: #2980b9;
    }
  </style>
</head>

<body>
  <div class="container">
    <h2>登録完了しました！</h2>
    <p>ご登録ありがとうございます。<br>続いてログインして使ってみてください。</p>
    <a href="login.php" class="btn">ログインページへ</a>
  </div>
</body>

</html>