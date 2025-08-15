<?php
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: text/plain; charset=utf-8');
echo "=== Rijschool Agenda – Login Diagnose ===\n\n";
echo "PHP version: " . PHP_VERSION . "\n";
echo "random_bytes: " . (function_exists('random_bytes')?'yes':'no') . "\n";
echo "openssl_random_pseudo_bytes: " . (function_exists('openssl_random_pseudo_bytes')?'yes':'no') . "\n";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "short_open_tag: " . (ini_get('short_open_tag')?'1':'0') . "\n";
echo "\nFiles:\n";
foreach (['includes/auth.php','includes/db.php','includes/app_header.php','includes/app_footer.php','config.php'] as $f) {
  echo str_pad($f, 28) . (file_exists(__DIR__ . '/' . $f)? "OK" : "MISSING") . "\n";
}
echo "\nDB connect test: ";
try { $pdo = db(); $pdo->query('SELECT 1'); echo "OK\n"; }
catch (Throwable $e) { echo "FAIL: " . $e->getMessage() . "\n"; }

echo "\nUsers table columns:\n";
try {
  $stmt = db()->query("SHOW COLUMNS FROM users");
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo " - {$col['Field']} ({$col['Type']})\n";
  }
} catch (Throwable $e) {
  echo "  (kon kolommen niet ophalen: " . $e->getMessage() . ")\n";
}

echo "\nTip: als je hier OK ziet, maar login nog 500 geeft, zet tijdelijk in login.php:\n";
echo "   ini_set('display_errors',1); error_reporting(E_ALL);\n";
echo "en herlaad de pagina om de echte foutmelding te zien.\n";
