<?php
session_start();
require_once('../library.php');
require_once __DIR__ . '/../models/Category.php';
// ログインチェック
$user_id = requireLogin();

$category_name = '';
$errors = [];

$categoryModel = new Category();
$categories = $categoryModel->findAllByUserId($user_id);

// フラッシュメッセージの取得
$message = $_SESSION['message'] ?? "";
unset($_SESSION['message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $category_name = trim($_POST['name']);
  if ($category_name === '') {
    $errors[] = 'カテゴリ名を入力してください。';
  }
  // 既に登録済みのカテゴリ名か確認する
  if (empty($errors)) {
    $db = dbConnect();
    $stmt = $db->prepare('
      SELECT count(*) FROM categories 
      WHERE name = :name AND user_id = :user_id
    ');
    $stmt->bindValue(':name', $category_name, PDO::PARAM_STR);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
    $stmt->execute();
    $count = (int)$stmt->fetchColumn();
    if ($count > 0) {
      $errors[] = $category_name . 'は既に登録済みです。';
    }
  }

  if (empty($errors)) {
    // $db = dbConnect();
    $stmt = $db->prepare("INSERT INTO categories (user_id, name, created_at) VALUE (:user_id, :name, Now())");
    // if (!$stmt) {
    //   die($db->error);
    // }
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':name', $category_name, PDO::PARAM_STR);
    $success = $stmt->execute();
    // if (!$success) {
    //   die($db->error);
    // }

    echo "カテゴリ「" . h($category_name) . "」を登録しました。";
    echo "<br><a href='add.php'>戻る</a>";
    exit();
  }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>カテゴリ登録</title>
  <link rel="stylesheet" href="../css/site_category.css?v=1.3">
</head>

<body>
  <div class="container">
    <h2>カテゴリ登録</h2>

    <?php if (!empty($errors)): ?>
      <ul style="color:red;">
        <?php foreach ($errors as $error): ?>
          <li><?php echo h($error); ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
      <p style="color:green;"><?php echo h($message) ?></p>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <!-- <label for="name">カテゴリ名</label> -->
        <input type="text" name="name" placeholder="カテゴリ名を入力" value="<?php echo h($category_name); ?>">
      </div>
      <button type="submit">登録</button>
      <!-- カテゴリ一覧表示 -->
      <h3>登録済みカテゴリ一覧</h3>
      <ul>
        <?php foreach ($categories as $cat): ?>
          <li>
            <?php echo $cat['name'] ?>
            <a href="delete.php?id=<?php echo $cat['id'] ?>">[削除]</a>
          </li>
        <?php endforeach; ?>
      </ul>

      <p><a class="back-link" href="../index.php">サイト一覧に戻る</a></p>
  </div>
  </form>
</body>

</html>