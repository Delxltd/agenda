<?php
require_once __DIR__ . '/includes/helpers.php';

$from = $_GET['from'] ?? date('Y-m-d');
$to   = $_GET['to']   ?? date('Y-m-d', strtotime('+1 month'));
$ins  = isset($_GET['instructor_id']) ? (int)$_GET['instructor_id'] : 0;

$pdo = db();
$sql = "SELECT a.*, u.name AS instructor_name, s.first_name, s.last_name
        FROM appointments a
        LEFT JOIN users u ON u.id=a.instructor_id
        LEFT JOIN students s ON s.id=a.student_id
        WHERE a.start >= ? AND a.start <= ?";
$params = [$from.' 00:00:00', $to.' 23:59:59'];
if ($ins) { $sql .= " AND a.instructor_id=?"; $params[] = $ins; }
$sql .= " ORDER BY a.start ASC";
$st = $pdo->prepare($sql); $st->execute($params); $rows = $st->fetchAll();

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename=agenda.ics');

echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Rijschool Agenda//NL\r\n";
foreach ($rows as $r) {
  $uid = 'appt-'.$r['id'].'@'.($_SERVER['HTTP_HOST'] ?? 'localhost');
  $start = (new DateTime($r['start']))->format('Ymd\\THis');
  $end   = (new DateTime($r['end']))->format('Ymd\\THis');
  $title = $r['title'] ?: ($r['type'].' — '.trim(($r['first_name']??'').' '.($r['last_name']??'')));
  $desc  = 'Instructeur: '.$r['instructor_name'];
  echo "BEGIN:VEVENT\r\nUID:$uid\r\nDTSTART:$start\r\nDTEND:$end\r\nSUMMARY:".str_replace("\n","\\n",$title)."\r\nDESCRIPTION:".str_replace("\n","\\n",$desc)."\r\nEND:VEVENT\r\n";
}
echo "END:VCALENDAR\r\n";
