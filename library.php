<?php

require_once __DIR__ . '/vendor/autoload.php'; // dotenv 用
use Dotenv\Dotenv;
// .env を読み込む
if (file_exists(__DIR__ . '/.env')) {
  $dotenv = Dotenv::createImmutable(__DIR__);
  $dotenv->load();
}

$dsn = sprintf(
  "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
  $_ENV['DB_HOST'] ?? 'localhost',
  $_ENV['DB_PORT'] ?? '3306',
  $_ENV['DB_NAME'] ?? 'myapp'
);

try {
  $pdo = new PDO(
    $dsn,
    $_ENV['DB_USER'] ?? 'root',
    $_ENV['DB_PASSWORD'] ?? 'password'
  );
} catch (PDOException $e) {
  exit('DB接続エラー: ' . $e->getMessage());
}
