<?php
require_once __DIR__ . '/includes/db.php';

$sql = [];
$sql[] = "CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','instructor') NOT NULL DEFAULT 'instructor',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$sql[] = "CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(50) NULL,
  address VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$sql[] = "CREATE TABLE IF NOT EXISTS appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  instructor_id INT NOT NULL,
  student_id INT NULL,
  type ENUM('lesson60','lesson90','lesson120','exam','block') NOT NULL DEFAULT 'lesson90',
  title VARCHAR(255) NULL,
  start DATETIME NOT NULL,
  end   DATETIME NOT NULL,
  status ENUM('planned','cancelled','completed') NOT NULL DEFAULT 'planned',
  notes TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_start (start),
  KEY idx_instr_start (instructor_id, start),
  CONSTRAINT fk_appt_instr FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_appt_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$pdo = db();
foreach ($sql as $q) { $pdo->exec($q); }

$admin = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='admin'"); $admin->execute();
if (!$admin->fetchColumn()) {
  $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)")
      ->execute(['Admin','admin@example.com', password_hash('admin123', PASSWORD_DEFAULT),'admin']);
}
$ins = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='instructor'"); $ins->execute();
if (!$ins->fetchColumn()) {
  $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)")
      ->execute(['Instructeur Jan','jan@example.com', password_hash('rijles123', PASSWORD_DEFAULT),'instructor']);
}
$st = $pdo->prepare("SELECT COUNT(*) FROM students"); $st->execute();
if (!$st->fetchColumn()) {
  $pdo->prepare("INSERT INTO students (first_name,last_name,email,phone) VALUES (?,?,?,?)")
      ->execute(['Michelle','Oosie','michelle@example.com','0612345678']);
}

$hasAppt = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
if (!$hasAppt) {
  $insId = (int)$pdo->query("SELECT id FROM users WHERE role='instructor' LIMIT 1")->fetchColumn();
  $stuId = (int)$pdo->query("SELECT id FROM students LIMIT 1")->fetchColumn();
  $start = (new DateTime('+1 day 10:00'))->format('Y-m-d H:i:s');
  $end   = (new DateTime('+1 day 11:30'))->format('Y-m-d H:i:s');
  $pdo->prepare("INSERT INTO appointments (instructor_id,student_id,type,title,start,end,status) VALUES (?,?,?,?,?,?,?)")
      ->execute([$insId,$stuId,'lesson90',null,$start,$end,'planned']);
}

echo "OK – database klaar. Login: admin@example.com / admin123";
