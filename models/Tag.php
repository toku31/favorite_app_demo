<?php
require_once(__DIR__ . '/BaseModel.php');
require_once(__DIR__ . '/TagRepositoryInterface.php');

class Tag extends BaseModel implements TagRepositoryInterface
{

  public function findAll(): array
  {
    $stmt = $this->db->query('
    SELECT * FROM tags ORDER BY id ASC
    ');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // ユーザIDでタグのオブジェクト取得
  public function findAllByUserId($user_id): ?array
  {
    $stmt = $this->db->prepare('
    SELECT * FROM tags WHERE user_id = :user_id
    ');
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result ?: null; // $resultがfalseのとき null
  }

  // タグIDでタグのオブジェクト取得
  public function findById($id): ?array
  {
    $stmt = $this->db->prepare('
    SELECT * FROM tags WHERE id = :id
    ');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null; // $resultがfalseのとき null
  }

  public function create($data): bool
  {
    $stmt = $this->db->prepare('
    Insert INTO tags (user_id, name, created_at) VALUES (:user_id, :name, Now())
    ');
    $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
    return $stmt->execute();
  }

  public function update($id, $data): bool
  {
    $stmt = $this->db->prepare('
    Update tags 
    SET user_id = :user_id,
        name = :name,
        updated_at = Now()
    WHERE id = :id
    ');
    return $stmt->execute([
      ':user_id' => $data['user_id'],
      ':name' => $data['name'],
      ':id' => $id
    ]);
  }

  public function delete($id): bool
  {
    $stmt = $this->db->prepare('
    DELETE FROM tags WHERE id = :id
    ');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
  }

  public function findByName($user_id, $name): ?array
  {
    $stmt = $this->db->prepare('
  SELECT * From tags WHERE user_id = :user_id AND name = :name
  ');
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':name', $name, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);  // 何もない（空の）とき　falseを返す
    // if (empty($result)) {
    //   return null;
    // }
    // return $result;
    return $result ?: null;  // $resultがfalseのとき nullに変換
  }

  public function findTagNamesBySiteIds(array $siteIds): array
  {
    if (empty($siteIds)) {
      return [];  // 引数が空のときは空配列を返す
    }
    // site_id IN (?, ?, ?) のようなSQLを動的に作るためのもの
    $inClause = implode(',', array_fill(0, count($siteIds), '?'));
    $sql = "
      SELECT st.site_id, t.name AS tag_name
      FROM site_tag st
      JOIN tags t ON st.tag_id = t.id
      WHERE st.site_id IN ($inClause)
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute($siteIds);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tagMap = [];
    if (!empty($rows)) {
      foreach ($rows as $row) {
        $site_id = $row['site_id'];
        $tagMap[$site_id][] = $row['tag_name'];
      }
      return $tagMap;
      // return $tagMap[$site_id]; // site_id をキーとしたタグ名配列（例： [1 => ['PHP', 'Laravel']]）
    } else {
      return [];
    }
  }
}
