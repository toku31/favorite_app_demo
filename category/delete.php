<?php
session_start();
require_once('../library.php');
require_once __DIR__ . '/../models/Category.php';
require_once('../models/Site.php');

// ログインチェック
$user_id = requireLogin();
// var_dump('1');
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$id) {
  die('不正なIDです。');
}

$categoryModel = new Category();
$category = $categoryModel->findById($id);
if ($category === null) {
  die('カテゴリは見つかりません');
};

// 使用中のサイトの件数をカウント
$siteModel = new Site();
$usageCnt = $siteModel->countByCategoryId($id);

?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>カテゴリ削除確認</title>
</head>

<body>
  <h2>カテゴリ削除確認</h2>
  <p>カテゴリ名：<?php echo $category['name'] ?></p>
  <?php if ($usageCnt > 0): ?>
    <p>このカテゴリは<?= $usageCnt ?>件のサイトで使用されてます</p>
  <?php else: ?>
    <p>このカテゴリは使用されていません
    <p>
    <?php endif; ?>
    <p>本当に削除してもいいですか？</p>
    <form action="delete_execute.php" method="post">
      <input type="hidden" name="id" value="<?php echo h($category['id']) ?>">
      <input type="hidden" name="name" value="<?php echo h($category['name']) ?>">
      <button type="submit">削除</button>
      <a href="category_add.php">キャンセル</a>
    </form>

</body>

</html>