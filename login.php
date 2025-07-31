<?php
session_start();
require_once('library.php');
$error = [];
$email = "";
$password = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // echo 'submit';
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    // var_dump($email);
    if ($email === "" || $password === "") {
        $error['login'] = 'blank';
    } else {
        try {
            // ログインチェック
            $db = dbConnect();
            $stmt = $db->prepare('select id, username, password from users where email = :email');
            // if (!$stmt) {
            //     die($db->error);
            // }
            $stmt->bindValue(':email', $email);
            $success = $stmt->execute();
            // if (!$success) {   
            //     die($db->error);
            // }
            // $stmt->bind_result($id, $name, $hash);
            // $stmt->fetch();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            // var_dump($hash);
            // if (!$user) {
            // die("ユーザ登録されていません。");
            // }
            if ($user && password_verify($password, $user['password'])) {
                // ログイン成功
                session_regenerate_id();
                $_SESSION['id'] = $user['id'];
                // echo $id;
                $_SESSION['username'] = $user['username'];
                // もしくは
                // $_SESSION['user'] = [
                //   'id' => $user['id'],
                //   'username' => $user['username']  
                // ];

                header('Location: index.php'); // ログイン後のページへ
                exit();
            } else {
                $error['login'] = 'failed';
            }
        } catch (PDOException $e) {
            die("データベースエラー" . h($e->getMessage()));
        }
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

        .login-container {
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

        input[type="text"],
        input[type="password"] {
            width: 95%;
            padding: 10px;
            margin-bottom: 16px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1em;
        }

        .error {
            color: red;
            font-size: 0.9em;
            margin: -12px 0 12px;
        }

        .btn {
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

        .btn:hover {
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
    <div class="login-container">
        <h2>ログイン</h2>
        <div class="lead">
            メールアドレスとパスワードでログインしてください。<br>
            <a href="register.php">ユーザー登録はこちら</a>
        </div>

        <form action="" method="post">
            <label for="email">メールアドレス</label>
            <input type="text" id="email" name="email" value="<?= h($email) ?>">
            <?php if (isset($error['login']) && $error['login'] === 'blank'): ?>
                <p class="error">* メールアドレスとパスワードを入力してください</p>
            <?php endif; ?>
            <?php if (isset($error['login']) && $error['login'] === 'failed'): ?>
                <p class="error">* ログインに失敗しました。正しい情報を入力してください</p>
            <?php endif; ?>

            <label for="password">パスワード</label>
            <input type="password" id="password" name="password" value="<?= h($password) ?>">

            <input type="submit" value="ログインする" class="btn">
        </form>
    </div>
</body>

</html>