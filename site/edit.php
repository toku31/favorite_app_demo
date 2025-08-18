<?php
// エラーを表示してくれる
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../library.php';
require_once('../models/Site.php');
require_once('../models/Category.php');
require_once('../models/Tag.php');
$user_id = requireLogin(); // ログインチェック
// var_dump($user_id);
$site_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
// var_dump($site_id );
$siteModel = new Site();
$site = $siteModel->findById($site_id);
//  var_dump($site);
if (!$site || $site["user_id"] != $user_id) {
  die('不正なアクセスです');
}

// 全カテゴリー (検索・絞込みに使う)
$db = dbConnect();
$stmt = $db->prepare("
  SELECT * From categories
  WHERE user_id = :user_id
  ORDER BY id ASC
  ");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// タグの取得
$tagModel = new Tag;
$user_tags = $tagModel->findAllByUserId($user_id);
// debug($user_tags);
$siteIdArray = [];
// debug($site_id);
$siteIdArray[] = $site_id;  // 今回はサイトIDを１つのみ渡す
$selected_tags_all_sites = $tagModel->findTagNamesBySiteIds($siteIdArray);
// パターン①
// if (!empty($selected_tags_all_sites)) {
//   $selected_tags = $selected_tags_all_sites[$site_id];
// } else {
//   $selected_tags = [];
// }
// パターン②
// $selected_tags = !empty($selected_tags_all_sites[$site_id]) ? $selected_tags_all_sites[$site_id] : [];
// パターン３
$selected_tags = $selected_tags_all_sites[$site_id] ?? [];
// debug($selected_tags);

// 更新ボタンがクリックされた時
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $url = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  $category_id = filter_input(INPUT_POST, 'category_id', FILTER_SANITIZE_NUMBER_INT);
  $note = filter_input(INPUT_POST, 'note', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  // $current_image = filter_input(INPUT_POST, 'current_image', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
  // $current_image =  isset($_POST['current_image']) ? filter_input(INPUT_POST, 'current_image', FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';
  $current_image = filter_input(INPUT_POST, 'current_image', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  $current_image = $current_image ?? '';
  $delete_image = isset($_POST['delete_image']) ? true : false;
  $tag_ids = filter_input(INPUT_POST, 'tag_ids', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
  if (empty($tag_ids)) {
    $tag_ids = [];
  }

  $errors = [];
  // debug($current_image);
  $filename = $current_image;
  // debug($filename);
  // 画像の処理
  $image = $_FILES['image'];
  // var_dump($image);
  // debug($image);
  if ($image['name'] !== "" && $image['error'] === 0) {
    // $type = mime_content_type($image['tmp_name']);
    // debug('1');
    $finfo = new finfo();
    $type = $finfo->file($image['tmp_name'], FILEINFO_MIME_TYPE);
    // var_dump($type);
    if ($type !== 'image/jpeg' && $type !== 'image/png') {
      // $errors['image'] = 'type';
      $errors[] = "画像はJPEGまたはPNG形式でアップロードしてください。";
    } else {
      $filename = date('YmdHis') . '_' . $image['name'];
      // var_dump($filename);
      if (!move_uploaded_file($image['tmp_name'], '../user_image/' . $filename)) {
        $errors[] = "画像のアップロードに失敗しました。";
      } else {
        // 古い画像を削除
        if (!empty($current_image) && file_exists('../user_image/' . $current_image)) {
          unlink('./user_image/' . $current_image);
        }
      }
    }
  } elseif ($delete_image && !empty($current_image)) {
    // debug('2');
    // チェックが入っていて、画像が存在している時、削除する
    if (file_exists('../user_image/' . $current_image)) {
      unlink('../user_image/' . $current_image);
    }
    // var_dump($filename);
    $filename = '';
  }

  // var_dump($filename);
  if (empty($errors)) {
    // debug('3');
    $siteModel = new Site();
    $siteModel->update([
      'id' => $site_id,
      'url' => $url,
      'title' => $title,
      'category_id' => $category_id,
      'note' => $note,
      'image' => $filename
    ]);

    // タグの更新
    // 一度サイトの全てのタグを削除する
    $db = dbConnect();
    $stmt = $db->prepare("
      DELETE FROM site_tag WHERE site_id = :site_id
    ");
    $stmt->bindValue(':site_id', $site_id, PDO::PARAM_INT);
    $stmt->execute();
    // 再登録
    $stmt = $db->prepare("
      INSERT INTO site_tag (site_id, tag_id) VALUES (:site_id, :tag_id) 
    ");
    foreach ($tag_ids as $tag_id) {
      $stmt->bindValue(':site_id', $site_id, PDO::PARAM_INT);
      $stmt->bindValue(':tag_id', $tag_id, PDO::PARAM_INT);
      $stmt->execute();
    }

    header('Location: index.php');
    exit();
  }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>サイト編集</title>
  <link rel="stylesheet" href="../css/style.css">
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
    <h2>サイト編集</h2>
    <form action="edit.php?id=<?php echo h($site['id']) ?>" method="post" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?php echo h($site['id']) ?? "" ?>">
      <label>サイトのURL:</label>
      <input type="text" name="url" value="<?php echo h($site['url']); ?>">

      <label>タイトル:</label>
      <input type="text" name="title" value="<?php echo h($site['title'] ?? ''); ?>">

      <label>カテゴリ:</label>
      <select name="category_id">
        <option value="">--- 選択してください --</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?php echo h($cat['id']) ?>"
            <?php echo ($site['category_id'] == $cat['id']) ? 'selected' : '' ?>>
            <?php echo h($cat['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <label>タグ（複数選択可）:</label>
      <?php if (isset($user_tags)): ?>
        <?php foreach ($user_tags as $tag): ?>
          <?php $isChecked = in_array($tag['name'], $selected_tags, true); ?>
          <input type="checkbox" name="tag_ids[]" value="<?php echo h($tag['id']) ?>" <?php echo $isChecked ? 'checked' : '' ?>>
          <?php echo h($tag['name']) ?><br>
        <?php endforeach; ?>
      <?php else: ?>
        <p>登録されたタグはありません。</P>
      <?php endif; ?>

      <label>メモ:</label>
      <textarea name="note"><?php echo h($site['note'] ?? '') ?></textarea>

      <label>現在の画像:</label>
      <?php if ($site['image']): ?>
        <img src="../user_image/<?php echo h($site['image']) ?>" width="90" height="90" alt="現在の画像" />
        <!-- <label> -->
        <input type="checkbox" name="delete_image" value="1"> 画像を削除する
        <!-- </label> -->
      <?php else: ?>
        <p>登録された画像はありません。</P>
      <?php endif; ?>
      <label>画像の再アップロード:</label>
      <input type="file" name="image">

      <input type="hidden" name="current_image" value="<?= h($site['image']) ?>">

      <p><button type="submit">更新する</button></p>
      <p><a href="index.php">戻る</a></p>

    </form>
  </div>
</body>

</html>