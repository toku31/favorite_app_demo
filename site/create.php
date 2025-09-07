<?php
session_start();
require_once '../library.php';
$db = dbConnect();

// 初期化
$errors = [];
$url = "";
$title = "";
$category_id = "";
$note = "";
$image = "";
$filename = "";

// ログインしているか確認
if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
  die('ログインしてください');
} else {
  $user_id = $_SESSION['id'];
}

// カテゴリ一覧を取得
$stmt = $db->prepare("
  SELECT id, name From categories
  WHERE user_id = :user_id
  ORDER BY id ASC
  ");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// タグ一覧を取得
$stmt = $db->prepare('
    SELECT * FROM tags WHERE user_id = :user_id
    ');
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: null;

// フォームが送信されたとき
if ($_SERVER['REQUEST_METHOD'] === "POST") {
  // $url = trim($_POST['url']);
  // $title = trim($_POST['title']);
  // $note = trim($_POST['note']);
  // $category_id = $_POST['category_id'];
  $url = trim(filter_input(INPUT_POST, 'url', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
  $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
  $category_id = trim(filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT));
  $note = trim(filter_input(INPUT_POST, 'note', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
  $tag_ids = filter_input(INPUT_POST, 'tags', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
  if (empty($tag_ids)) {
    $tag_ids = [];
  }
  // $image = trim(filter_input(INPUT_POST, 'image', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
  // if ($user_id === ""){
  //   $errors[] = "ログインしていない"; 
  // }
  if ($url === "") {
    $errors[] =  "URLを入力してください。";
  }

  // 同一ユーザのURL重複チェック
  $stmt = $db->prepare("SELECT count(*) FROM sites WHERE user_id = :user_id AND url = :url;");
  $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
  $stmt->bindValue(':url', $url, PDO::PARAM_STR);
  $stmt->execute();
  $count = $stmt->fetchColumn();
  if ($count > 0) {
    $errors[] = "このURLは既に登録されています。";
  }

  // 登録処理
  if (empty($errors)) {
    $stmt = $db->prepare('INSERT INTO sites (user_id, url, title, category_id, note, image) VALUES (:user_id, :url, :title, :category_id, :note, :image);');

    if (!$stmt) {
      die($db->$error);
    }
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':url', $url, PDO::PARAM_STR);
    if ($title === "") {
      $stmt->bindValue(':title', null, PDO::PARAM_NULL);
    } else {
      $stmt->bindValue(':title', $title, PDO::PARAM_STR);
    }
    if ($category_id === "") {
      $stmt->bindValue(':category_id', null, PDO::PARAM_NULL);
    } else {
      $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
    }
    if ($note === "") {
      $stmt->bindValue(':note', null, PDO::PARAM_NULL);
    } else {
      $stmt->bindValue(':note', $note, PDO::PARAM_STR);
    }

    // 画像の処理
    $image = $_FILES['image'];
    if ($image['name'] !== "" && $image['error'] === 0) {
      // $type = mime_content_type($image['tmp_name']);
      $finfo = new finfo();
      $type = $finfo->file($image['tmp_name'], FILEINFO_MIME_TYPE);
      // var_dump($type);
      if ($type !== 'image/jpeg' && $type !== 'image/png') {
        // $errors['image'] = 'type';
        $errors[] = "画像はJPEGまたはPNG形式でアップロードしてください。";
      } else {
        $filename = date('YmdHis') . '_' . $image['name'];
        var_dump($filename);
        if (!move_uploaded_file($image['tmp_name'], './user_image/' . $filename)) {
          $errors[] = "画像のアップロードに失敗しました。";
        }
      }
    }

    if ($filename === "") {
      $stmt->bindValue(':image', null, PDO::PARAM_NULL);
    } else {
      $stmt->bindValue(':image', $filename, PDO::PARAM_STR);
    }

    $success = $stmt->execute();
    if (!$success) {
      die($db->$error);
    } else {
      // タグの登録
      if (!empty($tag_ids)) {
        $new_site_id = $db->lastInsertId();      // 👇 最後に挿入されたIDを取得
        // debug($new_site_id);
        foreach ($tag_ids as $tag_id) {
          // debug($tag_id);
          $stmt = $db->prepare('
            INSERT INTO site_tag (site_id, tag_id) VALUES (:site_id, :tag_id);
          ');
          $stmt->bindValue(':site_id', $new_site_id, PDO::PARAM_INT);
          $stmt->bindValue(':tag_id', $tag_id, PDO::PARAM_INT);
          $stmt->execute();
        }
      }
    }
    header('Location: thanks.php');
    // echo "サイトを登録しました。<br>";
    // echo '<a href="create.php">戻る</a> | <a href="index.php">サイト管理</a>';
    exit();
  } else {
    //   foreach ($errors as $err):
    //     echo '<p style="color:red;">' . h($err)  .  "</p>";
    //   endforeach;
  }
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>サイト登録</title>
  <!-- <link rel="stylesheet" href="../css/style.css"> -->
  <style>
    .container {
      background-color: #f0f0f0;
      max-width: 1600px;
      margin: 0 auto;
      padding: 16px;
    }

    h2 {
      text-align: center;
      margin-bottom: 24px;
    }

    form {
      max-width: 600px;
      margin: 0 auto;
      /* 中央寄せ */
    }

    input[type="text"],
    select,
    textarea,
    input[type="file"] {
      width: 100%;
      padding: 8px;
      margin-top: 4px;
      border: 1px solid #ccc;
      border-radius: 6px;
      box-sizing: border-box;
    }
  </style>
</head>

<body>
  <div class="container">
    <h2>サイト登録</h2>
    <?php foreach ($errors as $err): ?>
      <p style="color:red;"><?php echo h($err) ?></p>
    <?php endforeach; ?>

    <form action="create.php" method="post" enctype="multipart/form-data">
      <p><a href="index.php">戻る</a></p>
      <label>サイトのURL:</label><br>
      <input type="text" name="url"><br><br>

      <label>タイトル:</label><br>
      <input type="text" name="title"><br><br>

      <label>カテゴリ:</label><br>
      <select name="category_id">
        <option value="">--- 選択してください --</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?php echo h($cat['id']); ?>">
            <?php echo h($cat['name']); ?>
          </option>
        <?php endforeach; ?>
      </select><br><br>

      <label>タグを選択（複数可）：</label><br>
      <?php foreach ($tags as $tag): ?>
        <label>
          <input type="checkbox" name="tags[]" value="<?php echo h($tag['id']) ?>">
          <?php echo h($tag['name']) ?>
        </label><br>
      <?php endforeach; ?><br>

      <label>メモ:</label><br>
      <textarea name="note"></textarea><br><br>

      <label>画像:</label><br>
      <input type="file" name="image"><br><br>

      <button type="submit">登録する</button>
      <p><a href="index.php">戻る</a></p>
    </form>

  </div>
</body>

</html>