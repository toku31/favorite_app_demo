<?php
session_start();
require_once '../library.php';

// ログインチェック
if (!isset($_SESSION['id'])) {
  header('Location: login.php');
  exit();
} else {
  $user_id = $_SESSION['id'];
}

$db = dbConnect();
// 全カテゴリー (検索・絞込みに使う)
$stmt = $db->prepare("
  SELECT * From categories
  WHERE user_id = :user_id
  ORDER BY id ASC
  ");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 各ユーザの利用する全タグ(検索・絞込みに使う)
$stmt = $db->prepare('
    SELECT * FROM tags WHERE user_id = :user_id
');
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 自分の登録したサイトのみ取得（JOINでカテゴリ） テーブル表示に利用
// $stmt = $db->prepare('
//   SELECT 
//     s.*, 
//     c.name as category_name
//   FROM sites s 
//   LEFT JOIN categories c ON s.category_id = c.id
//   WHERE s.user_id = :user_id ORDER BY s.created_at DESC
// ');
// $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
// $stmt->execute();
// $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// サイトごとのタグ一覧を取得してまとめておく(タグ表示に利用)
$stmt = $db->query("
  SELECT st.site_id, t.name AS tag_name
  FROM site_tag st JOIN tags t ON st.tag_id = t.id
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$tagMap = [];
foreach ($rows as $row) {
  $site_id = $row['site_id'];
  $tagMap[$site_id][] = $row['tag_name'];
  // tagMap[1] = ["PHP", "Python"]
}

// リセットクリックでGET値を取得
$reset = filter_input(INPUT_GET, 'reset', FILTER_VALIDATE_INT);

if (!empty($reset)) {
  // リセット：条件・セッションを空に
  $category_id = null;
  $keyword = '';
  $tag_id = null;
  unset($_SESSION['search']);
} else {
  // 検索ボタンクリックでGET値を受け取り（未入力はnull）
  $category_id = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
  $raw_keyword = filter_input(INPUT_GET, 'keyword', FILTER_UNSAFE_RAW);
  $keyword = trim($raw_keyword ?? '');
  $tag_id = filter_input(INPUT_GET, 'tag_id', FILTER_VALIDATE_INT);
}

// 入力がなければ TOP の検索条件を引き継ぐ
if (empty($category_id) && empty($keyword) && empty($tag_id)) {
  $keyword = $_SESSION['search']['keyword'] ?? '';
  $category_id = $_SESSION['search']['category_id'] ?? null;
  $tag_id = $_SESSION['search']['tag_id'] ?? null;
  // unset($_SESSION['search']);
} else {
  // 新しい検索条件をセッションに保存
  $_SESSION['search'] = [
    'keyword' => $keyword,
    'category_id' => $category_id,
    'tag_id' => $tag_id,
  ];
}

// debug($keyword);
// debug($category_id);
// debug($user_id);
// debug($tag_id);

// サイト取得
$sites = findWithFilters($db, $user_id, $keyword, $category_id, $tag_id);

// pagination情報
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$limit = 12;
$offset = ($page - 1) * $limit;
// 検索・絞込みによる総件数
$totalCount = CountWithFilters($db, $user_id, $keyword, $category_id, $tag_id);
// debug($totalCount);
$totalPages = ceil($totalCount / $limit);
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- <link rel="stylesheet" href="../css/style.css"> -->
  <title>サイト管理</title>
  <link rel="stylesheet" href="../css/site_admin.css?v=1.3">
</head>

<body>
  <div class="container">
    <h2>サイト管理</h2>
    <p><a href="create.php">+サイトを追加する</a> | <a href="../index.php">Topに戻る</a></p>

    <form method="get" action="index.php" class="filter-search-form">
      <select name="category_id">
        <option value="">すべてのカテゴリ</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= h($cat['id']) ?>" <?php echo isset($_GET['category_id']) && $_GET['category_id'] == $cat['id'] ? 'selected' : '' ?>>
            <?= h($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <!-- タグ選択（1つだけ絞り込み） -->
      <select name="tag_id">
        <option value="">すべてのタグ</option>
        <?php foreach ($tags as $tag): ?>
          <option value="<?= h($tag['id']) ?>" <?= isset($_GET['tag_id']) && $_GET['tag_id'] == $tag['id'] ? 'selected' : '' ?>>
            <?= h($tag['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <input type="text" name="keyword" placeholder="タイトル・メモ検索" value="<?php echo h($_GET['keyword'] ?? '') ?>">
      <button type="submit">検索</button>
      <a href="index.php?reset=1" class="reset-btn">リセット</a>
    </form>

    <table>
      <tr>
        <th>URL</th>
        <th>タイトル</th>
        <th>カテゴリ</th>
        <th>タグ</th>
        <th>メモ</th>
        <th>登録日時</th>
        <th>操作</th>
      </tr>
      <?php foreach ($sites as $site): ?>
        <tr>
          <td><a href="<?php echo h($site['url']); ?>" target="_blank"><?php echo h($site['url']); ?></a></td>
          <td><?php echo h($site['title'] ?? ''); ?></td>
          <td><?php echo h($site['category_name'] ?? '未設定') ?></td>
          <!-- タグの表示 -->
          <?php if (isset($tagMap[$site['id']])) {
            $tags = $tagMap[$site['id']];
          } else {
            $tags = [];
          } ?>
          <!-- <?php debug($tags); ?> -->
          <td>
            <?php if (!empty($tags)): ?>
              <?php echo implode(', ', array_map('h', $tags)); ?>
            <?php else: ?>
              <?php {
                echo 'タグなし';
              } ?>
            <?php endif; ?>
          </td>
          <td><?php echo nl2br(h($site['note'] ?? '')); ?></td>
          <td><?php echo h($site['created_at']); ?></td>
          <td>
            <a href="edit.php?id=<?php echo h($site['id']); ?>">編集</a>
            <a href="delete.php?id=<?php echo h($site['id']) ?>" onClick="return confirm('本当に削除しますか？')">削除</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>

    <!-- ページネーション（前へ・次へ付き） -->
    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php
        // 前のページ
        if ($page > 1):
          $prevQuery = $_GET;
          $prevQuery['page'] = $page - 1;
          $prevUrl = '?' . http_build_query($prevQuery);
        ?>
          <a href="<?= $prevUrl ?>" class="prev">« 前へ</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <?php
          $query = $_GET;
          $query['page'] = $i;
          $url = '?' . http_build_query($query);
          ?>
          <a href="<?= $url ?>" class="<?= ($i == $page) ? 'active' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>

        <?php
        // 次のページ
        if ($page < $totalPages):
          $nextQuery = $_GET;
          $nextQuery['page'] = $page + 1;
          $nextUrl = '?' . http_build_query($nextQuery);
        ?>
          <a href="<?= $nextUrl ?>" class="next">次へ »</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</body>

</html>