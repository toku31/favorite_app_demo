<?php
session_start();
require_once '../library.php';
require_once('../models/Tag.php');
$user_id = requireLogin(); // ログインチェック

$tagModel = new Tag();
$tags = $tagModel->findAllByUserId($user_id);

?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>タグ管理</title>
  <link rel="stylesheet" href="../css/site_category.css?v=1.4">
</head>

<body>
  <div class="container">
    <h2>タグ登録</h2>
    <?php if (isset($_GET['message'])): ?>
      <p style="color:green;"><?php echo h($_GET['message']); ?></p>
    <?php endif; ?>
    <form action="create.php" method="post">
      <!-- <label>タグ名：</label> -->
      <input type="text" name="name" placeholder="タグ名を入力" required>
      <button type="submit">登録</button>
    </form>
    <h3>登録済みタグ一覧</h3>
    <?php if (isset($tags)): ?>
      <table>
        <tr>
          <th>ID</th>
          <th>タグ名</th>
          <th>操作</th>
        </tr>
        <?php foreach ($tags as $tag): ?>
          <tr>
            <td><?php echo h($tag['id']) ?></td>
            <td><?= h($tag['name']) ?></td>
            <td>
              <a href="edit.php?id=<?php echo h($tag['id']) ?>">編集</a>
              <a href="delete.php?id=<?php echo h($tag['id']) ?>" onClick="return confirm('本当に削除しますか？') ">削除</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p>タグは登録されていません。</p>
    <?php endif; ?>
    <p><a class="back-link" href="../index.php">サイト一覧に戻る</a></p>
  </div>
</body>

</html>