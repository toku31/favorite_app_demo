<?php

require_once __DIR__ . '/vendor/autoload.php'; // dotenv 用
use Dotenv\Dotenv;
// .env を読み込む
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// htmlspecialcharsを短くする
function h($value)
{
  // return htmlspecialchars($value, ENT_QUOTES);
  return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
  // return htmlspecialchars(($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function dbConnect()
{
  // エラーを画面に表示（開発中のみ）
  ini_set('display_errors', 1);
  error_reporting(E_ALL);

  // $host = 'localhost';
  // $dbname = 'favorite_app_private'; //作成したデータベース
  // $user = 'root';
  // $pass = 'root'; // MAMPのデフォルトは空（Windows）　Macでは'root'のこともあり
  $host = $_ENV['DB_HOST'];
  $dbname = $_ENV['DB_NAME'];
  $user = $_ENV['DB_USER'];
  $pass = $_ENV['DB_PASSWORD'];

  $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
  try {
    $db = new PDO($dsn, $user, $pass);
    // プリペアードステートメントのエミュレーションを無効にする
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    // 例外処理
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
  } catch (PDOException $e) {
    echo 'DB接続エラー' . $e->getMessage();
    exit();
  }
}

// ログインチェック
function requireLogin()
{
  // if (empty($_SESSION['user'] || !isset($_SESSION['id']))) {
  if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
  } else {
    $user_id = $_SESSION['id'];
    return $user_id;
  }
}

// デバッグ表示
function debug($var): void
{
  echo '<pre>';
  var_dump($var);
  echo '</pre>';
}

function isBlank(?string $value): bool
{
  return trim($value ?? '') === '';
}

function isTooLong(string $value, int $max): bool
{
  return mb_strlen($value) > $max;
}

function isInvalidEmail(string $email): bool
{
  return !filter_var($email, FILTER_VALIDATE_EMAIL);
}

// POSTリクエストかどうかの判定
function isPost(): bool
{
  return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// フォーム入力の取得
function getFormInput(string $key, $default = ''): string
{
  return $_POST[$key] ?? $default;
}

// ページリダイレクト
function redirect(string $url): void
{
  header("Location: $url");
  exit;
}


function findWithFilters($db, int $user_id, ?string $keyword = '', ?string $category_id = '', ?string $tag_id = '', int $limit = 12, int $offset = 0): array
{
  $sql = '
        SELECT s.*, c.name AS category_name
        FROM sites s
        LEFT JOIN categories c ON s.category_id = c.id
    ';

  // タグが選ばれている時、site_tagテーブルをINNER JOINする
  if (!empty($tag_id)) {
    $sql .= ' INNER JOIN site_tag st ON s.id = st.site_id';
  }

  // WHERE句
  $sql .= ' WHERE s.user_id = :user_id';
  $params = [':user_id' => $user_id];

  if (!empty($category_id)) {
    $sql .= ' AND s.category_id = :category_id';
    $params[':category_id'] = $category_id;
  }

  // タグ検索
  if (!empty($tag_id)) {   //  if ($tag_id !== '')
    $sql .= ' AND st.tag_id = :tag_id';
    $params[':tag_id'] = $tag_id;
  }

  if (!empty($keyword)) {
    $sql .= ' AND (s.title LIKE :kw1 OR s.note LIKE :kw2)';
    $params[':kw1'] = '%' . $keyword . '%';
    $params[':kw2'] = '%' . $keyword . '%';
  }

  // pagination情報
  $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
  $limit = 12;
  $offset = ($page - 1) * $limit;

  $sql .= ' ORDER BY s.created_at DESC Limit :limit OFFSET :offset';
  $params[':limit'] =  $limit;
  $params[':offset'] = $offset;

  $stmt = $db->prepare($sql);
  $stmt->execute($params);

  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 検索条件で絞って件数を取得する
function CountWithFilters($db, int $user_id, ?string $keyword = null, ?string $category_id = null, ?string $tag_id = null): int
{
  $sql = '
        SELECT count(*) AS filtered_count
        FROM sites s
        LEFT JOIN categories c ON s.category_id = c.id
    ';

  // タグが選ばれている時、site_tagテーブルをINNER JOINする
  if (!empty($tag_id)) {
    $sql .= ' INNER JOIN site_tag st ON s.id = st.site_id';
  }

  // WHERE句
  $sql .= ' WHERE s.user_id = :user_id';
  $params = [':user_id' => $user_id];

  if (!empty($category_id)) {
    $sql .= ' AND s.category_id = :category_id';
    $params[':category_id'] = $category_id;
  }

  // タグ検索
  if (!empty($tag_id)) {   //  if ($tag_id !== '')
    $sql .= ' AND st.tag_id = :tag_id';
    $params[':tag_id'] = $tag_id;
  }

  if (!empty($keyword)) {
    $sql .= ' AND (s.title LIKE :kw1 OR s.note LIKE :kw2)';
    $params[':kw1'] = '%' . $keyword . '%';
    $params[':kw2'] = '%' . $keyword . '%';
  }

  $sql .= ' ORDER BY s.created_at DESC';

  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  $count = $stmt->fetchColumn();
  return (int)$count;
}
