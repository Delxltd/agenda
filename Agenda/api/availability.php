<?php
require_once __DIR__ . '/../includes/helpers.php';

$duration = isset($_GET['duration']) ? intval($_GET['duration']) : 90;
$ws = isset($_GET['week_start']) ? $_GET['week_start'] : (new DateTime('monday this week'))->format('Y-m-d');
$instructor_id = isset($_GET['instructor_id']) ? intval($_GET['instructor_id']) : 0;

try { $wsDate = new DateTime($ws); } catch (Exception $e) { $wsDate = new DateTime('monday this week'); }

$days = [];
for ($i=0; $i<7; $i++) {
  $d = (clone $wsDate)->modify("+$i day");
  $slots = open_slots_for_day($d, $duration, $instructor_id ?: null);
  $days[] = ['date' => $d->format('Y-m-d'), 'slots' => $slots];
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['week_start' => $wsDate->format('Y-m-d'), 'days' => $days]);
