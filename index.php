<?php
session_start();
require_once 'library.php';

// ログインチェック
$user_id = requireLogin();
// DB接続
$db = dbConnect();

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

// 入力がなければ サイト管理画面 の検索条件を引き継ぐ
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

// 全カテゴリー
$stmt = $db->prepare("
  SELECT id, name From categories
  WHERE user_id = :user_id
  ORDER BY id ASC
  ");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 各ユーザの利用する全タグ
$stmt = $db->prepare('
    SELECT * FROM tags 
    WHERE user_id = :user_id
    ORDER BY id ASC
');
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 各サイトで登録している全タグ
$stmt = $db->query('
    SELECT st.site_id, t.name AS tag_name 
    FROM site_tag st
    JOIN tags t ON st.tag_id = t.id
');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
// site_id をキーにしたタグ名リスト（例: [1 => ['PHP', 'Laravel']])
$tagMap = [];
foreach ($rows as $row) {
    $tagMap[$row['site_id']][] = $row['tag_name'];
}

// 選択しているカテゴリIDの取得
// $category_id = trim((filter_input(INPUT_GET, 'category_id', FILTER_SANITIZE_NUMBER_INT)) ?? '');

// // 選択しているタグIDの取得
// $tag_id = trim((filter_input(INPUT_GET, 'tag_id', FILTER_SANITIZE_NUMBER_INT)) ?? '');

// $raw_keyword = filter_input(INPUT_GET, 'keyword', FILTER_UNSAFE_RAW);
// $keyword = trim($raw_keyword ?? '');
// debug($keyword);
// debug($category_id);
// debug($user_id);
// debug($tag_id);

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

// カテゴリが選択されていれば追加
if (!empty($category_id)) {   //  $category_id !== ''
    $sql .= ' AND s.category_id = :category_id';
    $params[':category_id'] =  $category_id;
}

// タグ検索
if (!empty($tag_id)) {   //  if ($tag_id !== '')
    $sql .= ' AND st.tag_id = :tag_id';
    $params[':tag_id'] = $tag_id;
}

// キーワード検索
if ($keyword !== '') {   //  if ($keyword)
    $sql .= ' AND (s.title LIKE :kw1 OR s.note LIKE :kw2) ';
    $params[':kw1'] =  '%' . $keyword . '%';
    $params[':kw2'] =  '%' . $keyword . '%';
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
$sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 全件数の取得（条件に合わせて書き換える）
// $countStmt = $db->prepare('SELECT COUNT(*) FROM sites WHERE user_id = :user_id');
// $countStmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
// $countStmt->execute();

// debug($keyword);
// debug($category_id);
// debug($user_id);
// debug($tag_id);
$totalCount = CountWithFilters($db, $user_id, $keyword, $category_id, $tag_id);
// debug($totalCount);
$totalPages = ceil($totalCount / $limit);

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登録サイト一覧</title>
    <link rel="stylesheet" href="css/style.css?v=1.8">
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <h2>お気に入りサイト一覧</h2>
        <p class="nav-links"><a href="site/index.php">サイト管理</a> |
            <a href="category/add.php">カテゴリ管理</a> |
            <a href="tag/index.php">タグ管理</a> |
            <a href="logout.php">ログアウト</a>
        </p>
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

        <div class="card-list">
            <?php foreach ($sites as $site): ?>
                <div class="card<?= empty($site['image']) ? ' no-image' : '' ?>" <?= !empty($site['image']) ? ' style="background-image: url(\'user_image/' . h($site['image']) . '\');"' : '' ?>>
                    <div class="card-content">
                        <a href="<?php echo h($site['url']); ?>" target="_blank">
                            <h3><?php echo h(isset($site['title']) ? $site['title'] : '(無題)'); ?></h3>
                        </a>
                        <p class="category">
                            <!-- <?php echo h($site['category_name'] ?? 'カテゴリ未選択'); ?> -->
                            <?php if (!empty($site['category_name'])): ?>
                                [<?php echo h($site['category_name']); ?>]
                            <?php else: ?>
                                [カテゴリ未選択]
                            <?php endif; ?>
                        </p>
                        <p class="tag">
                            <?php $tags = $tagMap[$site['id']] ?? []  ?>
                            <!-- <?php debug($tags) ?> -->
                            <?php if (!empty($tags)): ?>
                                <!-- <?php echo implode(', ', array_map('h', $tagMap[$site['id']])) ?> -->
                                <?php foreach ($tags as $tag): ?>
                                    <span class="tag-badge"><?= h($tag) ?? []  ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="tag-badge tag-empty">タグなし</span>
                            <?php endif; ?>
                            </え>
                            <!-- <br> -->
                        <p class="note"><?php echo nl2br(h($site['note'] ?? '')); ?></p>
                        <!-- <p>
                            <?php if ($site['image']): ?>
                                <img src="user_image/<?php echo h($site['image']); ?>" width="48" height="48" alt="" class="thumbnail">
                            <?php endif; ?>
                        </p> -->
                        <!-- <div class="card-actions">
                        <a href="site/edit.php?id=<?php echo h($site['id']); ?>" class="btn edit">編集</a>
                        <a href="site/delete.php?id=<?php echo h($site['id']); ?>" class="btn delete" onClick="return confirm('本当に削除しますか？')">削除</a>
                    </div> -->
                    </div>
                </div>
            <?php endforeach;  ?>
        </div>

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