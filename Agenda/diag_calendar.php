<?php
require_once __DIR__ . '/includes/helpers.php';
require_login();

$pdo = db();
$total = (int)$pdo->query('SELECT COUNT(*) FROM appointments')->fetchColumn();
$recent = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE start >= NOW() - INTERVAL 30 DAY AND start <= NOW() + INTERVAL 60 DAY")->fetchColumn();

$page_title = 'Agenda diagnostiek'; $nav_active = 'agenda';
require __DIR__ . '/includes/app_header.php';
?>
<div class="card">
  <h2>Agenda diagnostiek</h2>
  <p>Totaal afspraken: <strong><?= $total ?></strong></p>
  <p>Afspraken ~nu ±90 dagen: <strong><?= $recent ?></strong></p>

  <form method="get" action="/api/events.php" target="_blank" style="margin-top:10px">
    <label>Start</label> <input type="date" name="start" value="<?= date('Y-m-d') ?>" class="btn">
    <label>Einde</label> <input type="date" name="end" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" class="btn">
    <input type="hidden" name="debug" value="1">
    <button class="btn">Open API (debug)</button>
  </form>
</div>
<?php require __DIR__ . '/includes/app_footer.php'; ?>
