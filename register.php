<?php
// session_start();
require_once 'library.php';
$db = dbConnect();

//フォームが送信されたとき
if ($_SERVER['REQUEST_METHOD'] === "POST") {
  $email = $_POST['email'] ?? '';
  $username = $_POST['username'] ?? '';
  $password = $_POST['password'] ?? '';

  if ($email && $password) {
    // passwordをハッシュ化
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    try {
      // Insertする
      $stmt = $db->prepare("INSERT INTO users (email, username, password) VALUES (:email, :username, :password)");
      // $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
      $stmt->bindValue(':email', $email);
      $stmt->bindValue(':username', $username);
      $stmt->bindValue(':password', $hashedPassword);
      $success = $stmt->execute();
      if (!$success) {
        die($db->error);
      }
      echo "登録完了しました! . <br>";
      echo "<a href='login.php'>ログインはこちら</a>";
      exit();
    } catch (PDOException $e) {
      if ($e->errorInfo[1] === 1062) {
        echo "そのメールアドレスは既に登録されています";
      } else {
        echo "エラーが発生しました。" . h($e->getMessage());
      }
    }
  } else {
    echo "ユーザ名とパスワードを入力してください";
  }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <title>ログイン</title>
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
      margin: 0 auto;
      padding: 30px 24px;
      border-radius: 10px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    h2 {
      text-align: center;
      margin-bottom: 24px;
      font-size: 1.5em;
    }

    label {
      display: block;
      font-weight: bold;
      margin-bottom: 6px;
    }

    input[type="email"],
    input[type="text"],
    input[type="password"] {
      width: 94%;
      padding: 10px;
      margin-bottom: 16px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 1em;
      /* margin-right: 8px; */
      /* ← ここを追加 */
    }

    input:focus {
      border-color: #3498db;
      outline: none;
    }

    .error {
      color: red;
      font-size: 0.9em;
      margin: -12px 0 12px;
    }

    button {
      width: 100%;
      padding: 10px;
      background-color: #3498db;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 1em;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    button:hover {
      background-color: #2980b9;
    }

    .lead {
      text-align: center;
      font-size: 0.9em;
      margin-bottom: 16px;
    }

    .lead a {
      color: #3498db;
      text-decoration: none;
    }

    .lead a:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <div class="container">

    <!-- 登録フォーム -->
    <h2>ユーザ登録</h2>
    <form method="POST">
      メールアドレス<input type="email" name="email"><br>
      ユーザ名<input type="text" name="username"><br>
      パスワード<input type="password" name="password"><br>
      <button type="submit">登録</button>
    </form>
    <div class="lead">
      すでにアカウントをお持ちの方は <a href="login.php">ログイン</a>
    </div>
  </div>
</body>

</html>