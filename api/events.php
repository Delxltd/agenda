<?php
declare(strict_types=1);

/**
 * Events feed voor FullCalendar op appointments-tabel.
 * - Geeft ALTIJD geldige JSON terug.
 * - Als DB nog niet is ingesteld -> lege array [] i.p.v. fout-popup.
 * - Voeg ?debug=1 toe aan de URL om de echte fout als JSON te zien.
 */

ob_start(); // vang per ongeluk output uit includes op

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Expose-Headers: X-Event-Count');

if (function_exists('ini_set')) {
  ini_set('display_errors', '0'); // nooit PHP-warnings in de output
  ini_set('log_errors', '1');     // laat PHP in je error_log schrijven
}

require_once __DIR__ . '/../includes/helpers.php';
// Als helpers geen db() heeft, probeer onze db()
if (!function_exists('db')) {
  @require_once __DIR__ . '/../includes/db.php';
}

/* ----- Filters uit de query ----- */
$instructor_ids = [];
if (!empty($_GET['instructor_ids'])) {
  foreach (explode(',', $_GET['instructor_ids']) as $id) {
    $id = (int)trim($id); if ($id > 0) $instructor_ids[] = $id;
  }
} elseif (!empty($_GET['instructor_id'])) {
  $instructor_ids[] = (int)$_GET['instructor_id'];
}

$typesFilter = [];
if (!empty($_GET['types'])) {
  foreach (explode(',', $_GET['types']) as $t) {
    $t = trim($t); if ($t !== '') $typesFilter[] = $t;
  }
}

$startParam = $_GET['start']   ?? $_GET['startStr'] ?? null;
$endParam   = $_GET['end']     ?? $_GET['endStr']   ?? null;

try {
  $tz = new DateTimeZone(date_default_timezone_get() ?: 'Europe/Amsterdam');
  if ($startParam && $endParam) {
    $startDT = new DateTime($startParam);
    $endDT   = new DateTime($endParam);
  } else {
    $now = new DateTime('now', $tz);
    $startDT = (clone $now)->modify('-30 days');
    $endDT   = (clone $now)->modify('+60 days');
  }

  if (!function_exists('db')) {
    throw new RuntimeException('DB niet geconfigureerd (db() ontbreekt)');
  }
  $pdo = db();

  // Let op gereserveerde woorden start/end -> backticks
  $sql = "SELECT a.*, s.first_name, s.last_name, u.name AS instructor_name, u.id AS uid
          FROM appointments a
          LEFT JOIN students s ON s.id = a.student_id
          LEFT JOIN users u     ON u.id = a.instructor_id
          WHERE a.`start` < ? AND a.`end` > ?";
  $params = [$endDT->format('Y-m-d H:i:s'), $startDT->format('Y-m-d H:i:s')];

  if ($instructor_ids) {
    $place = implode(',', array_fill(0, count($instructor_ids), '?'));
    $sql .= " AND a.instructor_id IN ($place)";
    foreach ($instructor_ids as $id) $params[] = (int)$id;
  }
  if ($typesFilter) {
    $place = implode(',', array_fill(0, count($typesFilter), '?'));
    $sql .= " AND a.type IN ($place)";
    foreach ($typesFilter as $t) $params[] = $t;
  }
  $sql .= " ORDER BY a.`start` ASC";

  $st = $pdo->prepare($sql);
  $st->execute($params);
  $rows = $st->fetchAll();

  $labels = appt_labels(); $colors = appt_colors();
  $events = [];
  $insColorIdx = []; $idx=1;

  foreach ($rows as $a) {
    $type = $a['type'] ?? 'lesson60';
    $title = $labels[$type] ?? ucfirst((string)$type);
    $studentName = trim(($a['first_name'] ?? '').' '.($a['last_name'] ?? ''));
    if (!empty($a['title']) && $type === 'block') {
      $title = $a['title'];
    } elseif ($studentName !== '') {
      $title .= ' — '.$studentName;
    }

    // stabiele kleur-index per instructeur
    $uid = (int)($a['uid'] ?? 0);
    if ($uid && !isset($insColorIdx[$uid])) {
      $insColorIdx[$uid] = $idx; $idx = ($idx===12) ? 1 : $idx+1;
    }

    $cls = ['ins-'.($insColorIdx[$uid] ?? 1)];
    if (($a['status'] ?? '') === 'cancelled') $cls[] = 'is-cancelled';

    $events[] = [
      'id'    => (int)$a['id'],
      'title' => $title,
      'start' => (new DateTime($a['start']))->format(DateTime::ATOM),
      'end'   => (new DateTime($a['end']))->format(DateTime::ATOM),
      'color' => (($a['status'] ?? '') === 'cancelled') ? ($colors['cancelled'] ?? '#94a3b8') : ($colors[$type] ?? '#2563eb'),
      'classNames' => $cls,
      'extendedProps' => [
        'status'             => $a['status'] ?? 'planned',
        'type'               => $type,
        'typeLabel'          => $labels[$type] ?? $type,
        'studentName'        => $studentName,
        'instructorName'     => $a['instructor_name'] ?? '',
        'instructorId'       => $uid ?: null,
        'instructorColorIdx' => $insColorIdx[$uid] ?? 1,
        'notes'              => $a['notes'] ?? '',
      ],
    ];
  }

  header('X-Event-Count: ' . count($events));
  if (ob_get_length()) ob_clean(); // verwijder per ongeluk gegenereerde output
  echo json_encode($events, JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  // In productie: geef lege lijst terug (kalender blijft werken).
  // Voeg ?debug=1 toe om de echte fout als JSON te zien.
  $debug = isset($_GET['debug']) && $_GET['debug'] === '1';
  if (ob_get_length()) ob_clean();

  if ($debug) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
  } else {
    header('X-Event-Count: 0');
    echo '[]';
  }
  exit;
}
