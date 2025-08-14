<?php
require_once __DIR__ . '/auth.php';

function all_instructors(): array {
  $st = db()->query("SELECT id, name, email, role FROM users WHERE role='instructor' OR role='admin' ORDER BY name");
  return $st->fetchAll();
}
function all_students(): array {
  $st = db()->query("SELECT id, first_name, last_name FROM students ORDER BY last_name, first_name");
  return $st->fetchAll();
}

function appt_labels(): array {
  return [
    'lesson60'  => '60 minuten rijles',
    'lesson90'  => '90 minuten rijles',
    'lesson120' => '120 minuten rijles',
    'exam'      => 'Examen',
    'block'     => 'Blokkade',
  ];
}
function appt_colors(): array {
  return [
    'lesson60'  => '#10b981',
    'lesson90'  => '#3b82f6',
    'lesson120' => '#059669',
    'exam'      => '#2563eb',
    'block'     => '#f59e0b',
    'cancelled' => '#94a3b8',
  ];
}
