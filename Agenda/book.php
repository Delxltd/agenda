<?php
require __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/mailer.php';
verify_csrf();

$labels = appt_labels();
$durations = appt_durations();
$instructors = all_instructors();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $type = $_POST['type'] ?? 'lesson90';
  $dt = $_POST['datetime'] ?? '';
  $instructor_id = isset($_POST['instructor_id']) ? intval($_POST['instructor_id']) : 0;
  $first = trim($_POST['first_name'] ?? '');
  $last = trim($_POST['last_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');

  if (!$dt || !isset($durations[$type]) || !$instructor_id) {
    flash('Ontbrekende gegevens', 'error'); header('Location: /book.php'); exit;
  }

  $pdo = db();
  $student = $email ? find_student_by_email($email) : null;
  if (!$student) {
    $stmt = $pdo->prepare("INSERT INTO students (first_name, last_name, email, phone, status, portal_access) VALUES (?, ?, ?, ?, 'Actief', 1)");
    $stmt->execute([$first ?: 'Onbekend', $last ?: '', $email ?: null, $phone ?: null]);
    $student_id = (int)$pdo->lastInsertId();
  } else {
    $student_id = (int)$student['id'];
  }

  try { $start_dt = new DateTime($dt); } catch (Exception $e) { flash('Ongeldig tijdstip', 'error'); header('Location: /book.php'); exit; }
  $end_dt = (clone $start_dt)->modify('+' . $durations[$type] . ' minutes');

  $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM appointments WHERE status != 'cancelled' AND instructor_id = ? AND start < ? AND end > ?");
  $stmt->execute([$instructor_id, $end_dt->format('Y-m-d H:i:s'), $start_dt->format('Y-m-d H:i:s')]);
  if ((int)$stmt->fetch()['c'] > 0) { flash('Tijdslot is net geboekt. Kies een ander moment.', 'error'); header('Location: /book.php'); exit; }

  $stmt = $pdo->prepare("INSERT INTO appointments (student_id, instructor_id, type, start, end, status, notes) VALUES (?, ?, ?, ?, ?, 'planned', 'Geboekt via website')");
  $stmt->execute([$student_id, $instructor_id, $type, $start_dt->format('Y-m-d H:i:s'), $end_dt->format('Y-m-d H:i:s')]);

  $ins = find_user_by_id($instructor_id);
  $when = $start_dt->format('d-m-Y H:i');
  $course = $labels[$type] ?? 'Rijles';
  $htmlStudent = "<p>Hallo ".htmlspecialchars($first).",</p><p>Je afspraak is ingepland op <strong>$when</strong> voor <strong>$course</strong> met instructeur <strong>".htmlspecialchars($ins['name'])."</strong>.</p><p>Tot dan!</p>";
  if ($email) send_mail($email, "Bevestiging afspraak $when", $htmlStudent);
  global $MAIL_ADMIN;
  $htmlAdmin = "<p>Nieuwe boeking:</p><ul><li>Leerling: ".htmlspecialchars($first.' '.$last)." (".htmlspecialchars($email).", ".htmlspecialchars($phone).")</li><li>Instructeur: ".htmlspecialchars($ins['name'])."</li><li>Moment: $when</li><li>Type: $course</li></ul>";
  send_mail($MAIL_ADMIN, "Nieuwe boeking: $when — ".htmlspecialchars($ins['name']), $htmlAdmin);
  if (!empty($ins['email'])) send_mail($ins['email'], "Nieuwe boeking: $when", $htmlAdmin);

  flash('Afspraak staat ingepland. Je krijgt een bevestiging per e‑mail.', 'success');
  header('Location: /book.php'); exit;
}
?>
<div class="max-w-4xl mx-auto">
  <div class="card p-6">
    <h1 class="text-2xl font-semibold tracking-tight mb-1">Afspraak inplannen</h1>
    <p class="text-sm opacity-70 mb-4">Kies je instructeur en een passend tijdslot.</p>
    <form method="post" action="/book.php" class="space-y-6">
      <?= csrf_input() ?>
      <div class="grid md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm mb-1 opacity-70">Type</label>
          <select id="bookingType" name="type" class="select">
            <option value="lesson90"><?= htmlspecialchars($labels['lesson90']) ?></option>
            <option value="lesson60"><?= htmlspecialchars($labels['lesson60']) ?></option>
            <option value="lesson120"><?= htmlspecialchars($labels['lesson120']) ?></option>
            <option value="exam"><?= htmlspecialchars($labels['exam']) ?></option>
          </select>
        </div>
        <div>
          <label class="block text-sm mb-1 opacity-70">Instructeur</label>
          <select id="ins" name="instructor_id" class="select" required>
            <option value="">— Kies —</option>
            <?php foreach ($instructors as $ins): ?>
              <option value="<?= (int)$ins['id'] ?>"><?= htmlspecialchars($ins['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm mb-1 opacity-70">Naam</label>
          <div class="grid grid-cols-2 gap-2">
            <input name="first_name" placeholder="Voornaam" class="input">
            <input name="last_name" placeholder="Achternaam" class="input">
          </div>
        </div>
        <div>
          <label class="block text-sm mb-1 opacity-70">Contact</label>
          <div class="grid grid-cols-2 gap-2">
            <input type="email" name="email" placeholder="E‑mail" class="input">
            <input name="phone" placeholder="Telefoon" class="input">
          </div>
        </div>
      </div>

      <div>
        <label class="block text-sm mb-2 opacity-70">Beschikbare tijden</label>
        <div class="card p-3">
          <div id="weekGrid" class="grid grid-cols-2 md:grid-cols-7 gap-2 text-sm"></div>
        </div>
        <input id="datetime" type="hidden" name="datetime">
      </div>

      <div class="flex justify-end">
        <button class="btn btn-primary">Deze afspraak inplannen</button>
      </div>
    </form>
  </div>
</div>

<script>
  const typeToDuration = {lesson60:60, lesson90:90, lesson120:120, exam:60};

  function weekStart(d=new Date()) {
    const dt = new Date(d);
    const day = dt.getDay(); // 0=Sun
    const diff = (day === 0 ? -6 : 1 - day); // move to Monday
    dt.setDate(dt.getDate() + diff);
    dt.setHours(0,0,0,0);
    return dt;
  }

  async function loadGrid() {
    const type = document.getElementById("bookingType").value;
    const ins = document.getElementById("ins").value;
    if (!ins) { document.getElementById("weekGrid").innerHTML = "<div class='text-sm opacity-70'>Kies eerst een instructeur</div>"; return; }
    const duration = typeToDuration[type] || 90;
    const ws = weekStart();
    const wsISO = ws.toISOString().slice(0,10);
    const res = await fetch(`/api/availability.php?duration=${duration}&week_start=${wsISO}&instructor_id=${ins}`);
    const data = await res.json();
    renderGrid(data);
  }

  function renderGrid(data) {
    const wrap = document.getElementById("weekGrid");
    wrap.innerHTML = "";
    data.days.forEach((day) => {
      const col = document.createElement("div");
      col.className = "border border-black/10 dark:border-white/10 rounded-lg p-2 min-h-[220px]";
      const d = new Date(day.date + "T00:00");
      const title = d.toLocaleDateString("nl-NL", { weekday: "short", day: "2-digit", month: "short"});
      const h = document.createElement("div");
      h.className = "font-medium mb-2";
      h.textContent = title;
      col.appendChild(h);

      if (!day.slots.length) {
        const empty = document.createElement("div");
        empty.className = "text-neutral-400 text-xs";
        empty.textContent = "Niet beschikbaar";
        col.appendChild(empty);
      } else {
        day.slots.forEach(t => {
          const btn = document.createElement("button");
          btn.type = "button";
          btn.className = "w-full mb-1 px-2 py-1 border border-black/10 dark:border-white/10 rounded hover:bg-blue-50 dark:hover:bg-neutral-800 transition";
          btn.textContent = t;
          btn.addEventListener("click", (ev) => pickSlot(ev, day.date, t));
          col.appendChild(btn);
        });
      }
      wrap.appendChild(col);
    });
  }

  function pickSlot(event, dateStr, timeStr) {
    const dtISO = `${dateStr}T${timeStr}`;
    document.getElementById("datetime").value = dtISO;
    Array.from(document.querySelectorAll("#weekGrid button")).forEach(b => b.classList.remove("ring","ring-blue-500"));
    event.target.classList.add("ring","ring-blue-500");
  }

  document.getElementById("bookingType").addEventListener("change", loadGrid);
  document.getElementById("ins").addEventListener("change", loadGrid);
  document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("weekGrid").innerHTML = "<div class='text-sm opacity-70'>Kies eerst een instructeur</div>";
  });
</script>

<?php require __DIR__.'/includes/footer.php'; ?>
