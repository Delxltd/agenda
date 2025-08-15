<?php
// /appointments/create.php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

// --- helpers ---
function redirect_calendar(string $msg = '', bool $ok = true): void {
  if (session_status() === PHP_SESSION_NONE) session_start();
  $_SESSION['flash'] = ['msg'=>$msg, 'ok'=>$ok];
  header('Location: /calendar.php'); exit;
}
function bad(string $m){ header('Content-Type:text/plain; charset=utf-8'); http_response_code(400); echo $m; exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect_calendar('Ongeldige methode', false); }

// CSRF
$csrf = $_POST['csrf'] ?? '';
if (!hash_equals(csrf_token(), $csrf)) redirect_calendar('Sessie verlopen, probeer opnieuw.', false);

$labels = appt_labels();
$types  = array_keys($labels);

$u = current_user();
$role = $u['role'] ?? '';

// ---- read & validate input ----
$type = trim((string)($_POST['type'] ?? 'lesson90'));
if (!in_array($type, $types, true)) $type = 'lesson90';

$startRaw = (string)($_POST['start'] ?? '');
$endRaw   = (string)($_POST['end']   ?? '');
$title    = trim((string)($_POST['title'] ?? ''));
$notes    = trim((string)($_POST['notes'] ?? ''));
$student_id = $_POST['student_id'] ?? '';
$student_id = ($student_id === '' ? null : (int)$student_id);

// Instructor: admin mag kiezen, instructeur is zichzelf
if ($role === 'admin') {
  $instructor_id = (int)($_POST['instructor_id'] ?? 0);
  if ($instructor_id <= 0) $instructor_id = (int)($u['id'] ?? 0);
} else {
  $instructor_id = (int)($u['id'] ?? 0);
}

// Parse datetimes (from datetime-local -> "YYYY-MM-DDTHH:MM")
try {
  $tz = new DateTimeZone(date_default_timezone_get() ?: 'Europe/Amsterdam');
  $start = new DateTime($startRaw ?: 'now', $tz);
  $end   = new DateTime($endRaw   ?: 'now', $tz);
} catch (Throwable $e) {
  redirect_calendar('Ongeldige datum/tijd.', false);
}

if ($end <= $start) redirect_calendar('Eindtijd moet na starttijd liggen.', false);

// Business rule: vanaf 30 min t/m 8 uur
$dur = ($end->getTimestamp() - $start->getTimestamp())/60;
if ($dur < 30 || $dur > 8*60) redirect_calendar('Duur moet tussen 30 minuten en 8 uur zijn.', false);

// Blokkade mag zonder leerling; overige types bij voorkeur mét leerling (maar niet verplicht)
if ($type !== 'block' && $student_id === null) {
  // optioneel streng maken:
  // redirect_calendar('Selecteer een leerling voor dit type.', false);
}

// --- conflict controle (zelfde instructeur, overlap, niet geannuleerd) ---
try {
  $pdo = db();
  $sql = "SELECT a.id, a.start, a.end, s.first_name, s.last_name
          FROM appointments a
          LEFT JOIN students s ON s.id = a.student_id
          WHERE a.instructor_id = ?
            AND a.status <> 'cancelled'
            AND a.start < ? AND a.end > ?
          ORDER BY a.start ASC
          LIMIT 1";
  $st = $pdo->prepare($sql);
  $st->execute([$instructor_id, $end->format('Y-m-d H:i:s'), $start->format('Y-m-d H:i:s')]);
  $conflict = $st->fetch();
  if ($conflict) {
    $sname = trim(($conflict['first_name'] ?? '').' '.($conflict['last_name'] ?? ''));
    $msg = "Tijd conflict met bestaande afspraak (".$conflict['start']." - ".$conflict['end'].")".($sname?' voor '.$sname:'');
    redirect_calendar($msg, false);
  }
} catch (Throwable $e) {
  redirect_calendar('DB-fout bij conflictcontrole: '.$e->getMessage(), false);
}

// --- insert ---
try {
  $pdo = db();
  $sql = "INSERT INTO appointments (instructor_id, student_id, type, title, start, end, status, notes)
          VALUES (?, ?, ?, ?, ?, ?, 'planned', ?)";
  $st  = $pdo->prepare($sql);
  $st->execute([
    $instructor_id,
    $student_id,
    $type,
    ($type==='block' ? ($title ?: 'Blokkade') : $title),
    $start->format('Y-m-d H:i:s'),
    $end->format('Y-m-d H:i:s'),
    $notes
  ]);
} catch (Throwable $e) {
  redirect_calendar('Opslaan mislukt: '.$e->getMessage(), false);
}

redirect_calendar('Afspraak aangemaakt', true);
