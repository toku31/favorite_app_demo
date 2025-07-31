<?php
require_once __DIR__ . '/../library.php';

class Site
{
  private $db;

  public function __construct(){
    $this->db = dbConnect();
  }

  public function findAllByUserId($userId) {
    $stmt = $this->db->prepare(
    // 'select * from 
    // sites where user_id = :user_id order by sites.created_at desc'
    'select s.*, c.name as category_name from sites s left join categories c on s.category_id = c.id 
    where s.user_id = :userId
    order by s.created_at desc'
    );
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function findById($siteId) {
    $stmt = $this->db->prepare(
    // 'select * from sites where id = :id order by sites.created_at desc'
    'select s.*, c.name as category_name from sites s left join categories c on s.category_id = c.id where s.id = :id order by s.created_at desc'
    );
    $stmt->bindValue(':id', $siteId, PDO::PARAM_INT);
    $stmt->execute();
    // var_dump($stmt);
    return $stmt->fetch(PDO::FETCH_ASSOC); // １件のみ返す
  }

    // サイト作成
  Public function create($data) {
    $stmt = $this->db->prepare(
    'INSERT INTO sites (user_id, url, title, category_id, note, image, created_at) VALUES (:user_id, :url, :title, :category_id, :note, :image, Now())'
    );
    // if (!$stmt) {
    //   die($db->error);
    // }
    $stmt->execute([
      ':user_id' => $data['user_id'],
      ':url' => $data['url'],
      ':title' => $data['title'] ?: null,
      ':category_id' => $data['category_id'] ?: null,
      ':note' => $data['note'] ?: null,
      ':image' => $data['image'] ?: null
    ]);
  }

  // サイト更新
  public function update($data) {
    $stmt = $this->db->prepare('
      UPDATE sites
      SET url = :url,
          title = :title,
          category_id = :category_id,
          note = :note,
          image = :image,
          created_at = Now()
      WHERE id = :id
    ');

    return $stmt->execute([
      ':id' => $data['id'],
      ':url' => $data['url'],
      ':title' => $data['title'] ?: null,
      ':category_id' => $data['category_id'] ?: null,
      ':note' => $data['note'] ?: null,
      ':image' => $data['image'] ?: null
    ]);
  }

  Public function deleteById($siteId) {
    $stmt = $this->db->prepare('
    DELETE FROM sites WHERE id = :id; 
    ');
    $stmt->bindValue(':id', $siteId, PDO::PARAM_INT);
    return $stmt->execute();   // ← 実行結果（true/false）を返している
  }

  // カテゴリ使用数を取得
  Public function countByCategoryId($categoryId) {
    $stmt=$this->db->prepare('
    SELECT count(*) FROM sites WHERE category_id = :categoryId
    ');
    $stmt->bindValue(':categoryId', $categoryId, PDO::PARAM_INT);
    $stmt->execute();
    return (int)$stmt->fetchColumn(); // 結果は数値０以上
  }

}


?>