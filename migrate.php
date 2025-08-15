<?php
ini_set('display_errors',1); error_reporting(E_ALL);
require __DIR__.'/includes/helpers.php';

$pdo = db();
$driver = $GLOBALS['DB_DRIVER'] ?? 'mysql';
$file = __DIR__ . '/sql/schema.' . ($driver === 'mysql' ? 'mysql' : 'sqlite') . '.sql';

if (!file_exists($file)) { die("Schema bestand niet gevonden: $file"); }

$sql = file_get_contents($file);
try {
  $pdo->exec($sql);
  echo "✅ Schema uitgevoerd voor driver: $driver";
} catch (Throwable $e) {
  echo "❌ Fout bij schema: " . $e->getMessage();
}
