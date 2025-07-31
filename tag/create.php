<?php
session_start();
require_once '../library.php';
require_once('../models/Tag.php');
$user_id = requireLogin(); // ログインチェック

$name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS)); // trim(null) は　''空文字を返す
// if (trim($name ?? "") === "") {
//   die('タグ名を入力してください。');
// }
if ($name === '' ) { 
  die('タグ名を入力してください。');
} else {
  $tagModel = new Tag();
  $result = $tagModel->findByName($user_id, $name);
  if ($result !== null) {
    die($name . 'は既に登録済みです');
  }
}
// if (trim($name ?? "") === "") {
//   die('タグ名を入力してください。');
// }



$success = $tagModel->create([
  'user_id' => $user_id,
  'name' => $name 
]);

if ($success) {
  header('location: index.php');
  exit();
} else {
  die('タグの登録に失敗');
}

?>