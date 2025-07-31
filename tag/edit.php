<?php
session_start();
require_once '../library.php';
require_once('../models/Tag.php');
$user_id = requireLogin(); // ログインチェック
$tagModel = new Tag();
$errors = [];
$name = '';
$tag_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$tag_id) {  // falseが返ってきた時
  die('IDが不正です。');
}

// 初期表示　タグ情報を取得
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $tag = $tagModel->findById($tag_id);
  // 存在チェックと所有者チェック
  if (!isset($tag) || $tag['user_id'] !== $user_id) {
    die('不正なアクセスです。');
  }
  $name = $tag['name'];
}

// フォーム送信
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS)); // trim(null) は　''空文字を返す

  if ($name === '') {
    $errors[] = 'タグ名を入力してください。';
  } else {
    $tagModel = new Tag();
    $result = $tagModel->findByName($user_id, $name);
    if ($result !== null) {
      $errors[] = $name . 'は既に登録済みです';
    }
  }

  if (empty($errors)) {  //  count($errors) === 0
    $success = $tagModel->update($tag_id, [
      'user_id' => $user_id,
      'name' => $name
    ]);

    if ($success) {
      header('location: index.php');
      exit();
    } else {
      $errors[] = 'タグの更新に失敗しました。';
    }
  }
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>タグ編集</title>
</head>

<body>
  <h2>タグ編集</h2>

  <?php if (!empty($errors)):  ?>
    <ul style="color:red;">
      <?php foreach ($errors as $error): ?>
        <li><?php echo h($error) ?> </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="POST">
    <input type="hidden" name="id" value="<?php echo h($tag_id) ?>">
    <label>タグ名：</label>
    <input type="text" name="name" value="<?= h($name) ?>" required>
    <button type="submit">更新</button>
  </form>
  <p><a href="index.php">タグ一覧に戻る</a></p>
</body>

</html>