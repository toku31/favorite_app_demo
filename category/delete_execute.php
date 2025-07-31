<?php
session_start();
require_once('../library.php');
require_once('../models/Category.php');

requireLogin();

$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (!$id || !$name) {
  die('不正なアクセスです');
}

$catModel = new Category();
$catModel->deleteById($id);
$_SESSION['message'] = "カテゴリ「" . $name  . "」を削除しました。";
header('Location: add.php');
exit();
