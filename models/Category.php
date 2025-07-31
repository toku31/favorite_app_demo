<?php
require_once __DIR__ . '/../library.php';

class Category
{
  private $db;

  public function __construct()
  {
    $this->db = dbConnect();
  }

  public function findAll()
  {
    $stmt = $this->db->query('SELECT * from categories');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function findAllByUserId($user_id)
  {
    $stmt = $this->db->prepare('
    SELECT * FROM categories
    WHERE user_id = :user_id
    ');
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function findById($id)
  {
    try {
      $stmt = $this->db->prepare('
        SELECT * from categories WHERE id = :id
        ');
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      // 見つからない時はnullを返す
      return $result ?: null;
    } catch (PDOException $e) {
      // ログを出力すか　開発なら表示する
      error_log('DB ERROR:' . $e->getMessage());
      // 例外が発生した時もnullを返す
      return null;
    }
  }

  public function deleteById($id)
  {
    $stmt = $this->db->prepare('
      DELETE FROM categories WHERE id = :id;
      ');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
  }
}
