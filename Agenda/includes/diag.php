<?php
ini_set('display_errors',1); error_reporting(E_ALL);
require __DIR__.'/includes/helpers.php';
try {
  $pdo = db();
  echo "✅ DB-verbinding ok (driver: " . ($GLOBALS['DB_DRIVER'] ?? 'mysql') . ")<br>";
  echo "DB DSN actief.";
} catch (Throwable $e) {
  echo "❌ DB-fout: " . $e->getMessage();
}
