<?php
require_once 'library.php';
$stmt = $pdo->query("SELECT * FROM messages");
foreach ($stmt as $row) {
  echo "メッセージ: " . $row['content'] . "<br>";
}
