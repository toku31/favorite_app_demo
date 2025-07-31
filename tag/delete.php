<?php
session_start();
require_once '../library.php';
require_once('../models/Tag.php');
$user_id = requireLogin(); // ログインチェック
$tag_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$tag_id) {   // falseが返ってきた時
  die('IDが不正です。');
}

$tagModel = new Tag();
$tag = $tagModel->findById($tag_id);

// 存在チェックと所有者チェック
if (!isset($tag) || $tag['user_id']!== $user_id) {
  die('不正なアクセスです。');
}

// 削除処理
$success = $tagModel->delete($tag_id);
if ($success) {
  header('Location: index.php?message=タグを削除しました');
  exit();
} else {
  die('削除に失敗しました');
}

?>