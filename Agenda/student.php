<?php
require __DIR__.'/includes/auth.php'; require_login();
require __DIR__.'/includes/header.php';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$s = find_student_by_id($id);
if (!$s) { echo "<p class='opacity-70'>Leerling niet gevonden.</p>"; require __DIR__.'/includes/footer.php'; exit; }

$pdo = db();
$stmt = $pdo->prepare("SELECT type, start, end, status FROM appointments WHERE student_id = ? AND status != 'cancelled'");
$stmt->execute([$id]);
$appts = $stmt->fetchAll();
$counts = ['lesson60'=>0,'lesson90'=>0,'lesson120'=>0,'exam'=>0];
$minutes = 0;
foreach ($appts as $a) {
  $counts[$a['type']] = ($counts[$a['type']] ?? 0) + 1;
  $start = new DateTime($a['start']); $end = new DateTime($a['end']);
  $minutes += max(0, ($end->getTimestamp() - $start->getTimestamp())/60);
}
$total_hours = round($minutes/60, 1);
$labels = appt_labels();
?>
<div class="mb-6">
  <h1 class="text-2xl font-semibold tracking-tight"><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?></h1>
  <div class="text-sm opacity-70">Status: <?= htmlspecialchars($s['status']) ?></div>
</div>

<div class="grid md:grid-cols-2 gap-6">
  <div class="card p-5">
    <h2 class="font-semibold mb-3">Logboek samenvatting</h2>
    <div class="space-y-2 text-sm">
      <div class="flex items-center justify-between"><span><?= htmlspecialchars($labels['lesson90']) ?></span><span class="font-medium"><?= (int)$counts['lesson90'] ?>x</span></div>
      <div class="flex items-center justify-between"><span><?= htmlspecialchars($labels['lesson120']) ?></span><span class="font-medium"><?= (int)$counts['lesson120'] ?>x</span></div>
      <div class="flex items-center justify-between"><span><?= htmlspecialchars($labels['lesson60']) ?></span><span class="font-medium"><?= (int)$counts['lesson60'] ?>x</span></div>
      <div class="flex items-center justify-between"><span><?= htmlspecialchars($labels['exam']) ?></span><span class="font-medium"><?= (int)$counts['exam'] ?>x</span></div>
      <hr class="border-black/10 dark:border-white/10">
      <div class="flex items-center justify-between"><span>Totaal</span><span class="font-semibold"><?= htmlspecialchars($total_hours) ?> uur</span></div>
    </div>
  </div>

  <div class="card p-5">
    <h2 class="font-semibold mb-3">Contact</h2>
    <div class="text-sm space-y-1 opacity-90">
      <div>E‑mail: <?= htmlspecialchars($s['email'] ?: '—') ?></div>
      <div>Telefoon: <?= htmlspecialchars($s['phone'] ?: '—') ?></div>
      <div>Adres: <?= htmlspecialchars($s['address'] ?: '—') ?></div>
    </div>
  </div>
</div>

<?php require __DIR__.'/includes/footer.php'; ?>
