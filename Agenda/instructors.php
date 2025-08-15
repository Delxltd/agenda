<?php
require_once __DIR__ . '/includes/helpers.php'; require_login();
$page_title = 'Instructeurs'; $nav_active = 'instructors';
require __DIR__ . '/includes/app_header.php';
$rows = all_instructors();
?>
<div class="card">
  <h2>Instructeurs</h2>
  <ul>
    <?php foreach ($rows as $r): ?><li><?= h($r['name'].' ('.$r['email'].')') ?> — <?= h($r['role']) ?></li><?php endforeach; ?>
  </ul>
</div>
<?php require __DIR__ . '/includes/app_footer.php'; ?>
