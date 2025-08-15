<?php
require __DIR__.'/includes/auth.php'; require_login(); require __DIR__.'/includes/header.php';
$pdo = db();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$inv = $pdo->prepare("SELECT i.*, s.first_name, s.last_name FROM invoices i JOIN students s ON s.id = i.student_id WHERE i.id = ?");
$inv->execute([$id]);
$inv = $inv->fetch();
if (!$inv) { echo "<p>Factuur niet gevonden.</p>"; require __DIR__.'/includes/footer.php'; exit; }
$items = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?"); $items->execute([$id]); $items=$items->fetchAll();
$payments = $pdo->prepare("SELECT * FROM payments WHERE invoice_id = ? ORDER BY date"); $payments->execute([$id]); $payments=$payments->fetchAll();
$subtotal = invoice_subtotal($id);
$vat21 = invoice_vat($id, 0.21);
$vat6  = invoice_vat($id, 0.06);
$total = invoice_total($id);
$paid  = invoice_paid($id);
$open  = max(0, $total - $paid);
?>
<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold">Factuur <?= h($inv['number']) ?></h1>
  <div>
    <?php if ($open <= 0): ?>
      <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">Afgehandeld</span>
    <?php else: ?>
      <span class="px-2 py-1 text-xs rounded bg-orange-100 text-orange-800">Openstaand</span>
    <?php endif; ?>
  </div>
</div>
<div class="grid md:grid-cols-3 gap-6">
  <div class="md:col-span-2 bg-white border rounded p-4">
    <div class="flex items-center justify-between mb-2">
      <div class="font-medium"><?= h($inv['first_name'].' '.$inv['last_name']) ?></div>
      <div class="text-sm text-gray-500"><?= (new DateTime($inv['date']))->format('d-m-Y') ?></div>
    </div>
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b bg-gray-50">
          <th class="text-left p-2">Omschrijving</th>
          <th class="text-right p-2">Bedrag</th>
          <th class="text-left p-2">BTW</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
        <tr class="border-b">
          <td class="p-2"><?= h($it['description']) ?></td>
          <td class="p-2 text-right"><?= h(format_eur($it['amount'])) ?></td>
          <td class="p-2"><?= (int)round($it['vat_rate']*100) ?>%</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="mt-4 text-sm">
      <div class="flex justify-end"><div class="w-48">Subtotaal</div><div class="w-32 text-right"><?= h(format_eur($subtotal)) ?></div></div>
      <div class="flex justify-end"><div class="w-48">21% BTW</div><div class="w-32 text-right"><?= h(format_eur($vat21)) ?></div></div>
      <div class="flex justify-end"><div class="w-48">6% BTW</div><div class="w-32 text-right"><?= h(format_eur($vat6)) ?></div></div>
      <hr class="my-2">
      <div class="flex justify-end font-semibold"><div class="w-48">Totaal</div><div class="w-32 text-right"><?= h(format_eur($total)) ?></div></div>
    </div>
  </div>
  <div class="bg-white border rounded p-4">
    <h2 class="font-semibold mb-2">Betalingen</h2>
    <div class="text-sm space-y-2">
      <?php foreach ($payments as $p): ?>
        <div class="flex items-center justify-between">
          <div><?= (new DateTime($p['date']))->format('d-m-Y') ?> — <?= h($p['method']) ?></div>
          <div><?= h(format_eur($p['amount'])) ?></div>
        </div>
      <?php endforeach; ?>
      <hr>
      <div class="flex items-center justify-between font-medium">
        <div>Openstaand</div>
        <div><?= h(format_eur($open)) ?></div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
