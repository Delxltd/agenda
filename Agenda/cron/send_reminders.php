<?php
// Cron: verstuur 24u en 2u vooraf reminders (1x per afspraak)
// Run via CLI of web: php cron/send_reminders.php  of  /cron/send_reminders.php?token=...
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/config.php';

// beveilig eenvoudige webtoegang
if (php_sapi_name() !== 'cli') {
  if (!isset($_GET['token']) || $_GET['token'] !== $GLOBALS['CRON_TOKEN']) {
    http_response_code(403); echo "Forbidden"; exit;
  }
}

$now = new DateTime('now');
$in24 = (clone $now)->modify('+24 hours');
$in25 = (clone $now)->modify('+25 hours');

$in2  = (clone $now)->modify('+2 hours');
$in2p = (clone $now)->modify('+3 hours');

$pdo = db();

// 24u reminder
$sql24 = "SELECT a.*, s.first_name, s.last_name, s.email AS student_email, u.name AS instructor_name, u.email AS instructor_email
          FROM appointments a
          LEFT JOIN students s ON s.id = a.student_id
          LEFT JOIN users u ON u.id = a.instructor_id
          WHERE a.status != 'cancelled'
            AND a.student_id IS NOT NULL
            AND (a.reminder24_sent IS NULL OR a.reminder24_sent = 0)
            AND a.start >= ? AND a.start < ?";
$st = $pdo->prepare($sql24); $st->execute([$in24->format('Y-m-d H:i:s'), $in25->format('Y-m-d H:i:s')]);
$rows = $st->fetchAll();

foreach ($rows as $a) {
  $when = (new DateTime($a['start']))->format('d-m-Y H:i');
  $labels = appt_labels(); $course = $labels[$a['type']] ?? 'Rijles';
  $studentName = trim(($a['first_name']??'').' '.($a['last_name']??''));
  $html = "<p>Hallo ".h($studentName).",</p><p>Herinnering: je hebt morgen om <strong>$when</strong> een <strong>$course</strong>.</p>";
  if (!empty($a['student_email'])) send_mail($a['student_email'], "Herinnering (24u): $course op $when", $html);
  if (!empty($a['instructor_email'])) {
    $htmlIns = "<p>Herinnering: $course met <strong>".h($studentName)."</strong> op $when.</p>";
    send_mail($a['instructor_email'], "Herinnering (24u): $course — $when", $htmlIns);
  }
  $pdo->prepare("UPDATE appointments SET reminder24_sent = 1 WHERE id = ?")->execute([$a['id']]);
}

// 2u reminder
$sql2 = "SELECT a.*, s.first_name, s.last_name, s.email AS student_email, u.name AS instructor_name, u.email AS instructor_email
          FROM appointments a
          LEFT JOIN students s ON s.id = a.student_id
          LEFT JOIN users u ON u.id = a.instructor_id
          WHERE a.status != 'cancelled'
            AND a.student_id IS NOT NULL
            AND (a.reminder2_sent IS NULL OR a.reminder2_sent = 0)
            AND a.start >= ? AND a.start < ?";
$st = $pdo->prepare($sql2); $st->execute([$in2->format('Y-m-d H:i:s'), $in2p->format('Y-m-d H:i:s')]);
$rows2 = $st->fetchAll();

foreach ($rows2 as $a) {
  $when = (new DateTime($a['start']))->format('d-m-Y H:i');
  $labels = appt_labels(); $course = $labels[$a['type']] ?? 'Rijles';
  $studentName = trim(($a['first_name']??'').' '.($a['last_name']??''));
  $html = "<p>Hallo ".h($studentName).",</p><p>Herinnering: over ~2 uur heb je een <strong>$course</strong> om <strong>$when</strong>.</p>";
  if (!empty($a['student_email'])) send_mail($a['student_email'], "Herinnering (2u): $course om $when", $html);
  if (!empty($a['instructor_email'])) {
    $htmlIns = "<p>Herinnering: $course met <strong>".h($studentName)."</strong> om $when.</p>";
    send_mail($a['instructor_email'], "Herinnering (2u): $course — $when", $htmlIns);
  }
  $pdo->prepare("UPDATE appointments SET reminder2_sent = 1 WHERE id = ?")->execute([$a['id']]);
}

echo "OK " . $now->format('Y-m-d H:i:s') . PHP_EOL;
