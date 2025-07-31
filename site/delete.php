<?php 
session_start();
require_once '../library.php';
require_once('../models/Site.php');

// require_once 'library.php';
// require_once('models/Site.php');

echo "1";
// ログイン確認
$user_id = requireLogin();

$post_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
var_dump($post_id);
if (!$post_id) {
  header('Location: index.php');
  exit();
}

$siteModel = new Site();
$site = $siteModel->findById($post_id);
var_dump($site);
if (!$site||$site['user_id'] != $user_id){
  die('不正なアクセスです');
}

// 画像があれば削除
if (!empty($site['image'])) {
  $imagePath = __DIR__ . '/user_image/' . $site['image'];
  if (file_exists($imagePath)) {
    unlink($imagePath);
  }
}

// データベースから削除
if (!$siteModel->deleteById($post_id)) {
  echo "削除に失敗しました。";
} {
  header('Location: index.php');
  exit();
}

?>