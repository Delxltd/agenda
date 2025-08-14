<?php
require_once __DIR__ . '/includes/helpers.php'; require_login();
$page_title = 'Leerlingen'; $nav_active = 'students';
require __DIR__ . '/includes/app_header.php';
$rows = all_students();
?>
<div class="card">
  <h2>Leerlingen</h2>
  <ul>
    <?php foreach ($rows as $r): ?><li><?= h($r['first_name'].' '.$r['last_name']) ?></li><?php endforeach; ?>
  </ul>
</div>
<?php require __DIR__ . '/includes/app_footer.php'; ?>
