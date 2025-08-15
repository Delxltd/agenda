<?php
// /api/update_event.php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php'; require_login();
require __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

function jerr(string $msg, int $code=400){ http_response_code($code); echo json_encode(['ok'=>false,'error'=>$msg], JSON_UNESCAPED_UNICODE); exit; }
function jok(array $extra=[]){ echo json_encode(['ok'=>true]+$extra, JSON_UNESCAPED_UNICODE); exit; }

// ---- JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw ?: 'null', true);
if (!is_array($data)) jerr('Invalid JSON');

// ---- CSRF (FIX): lees uit sessie, niet opnieuw genereren
session_start();
$provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_CSRF'] ?? ($data['csrf'] ?? null);
// Probeer bekende sessiesleutels
$sessionToken = $_SESSION['csrf'] ?? $_SESSION['csrf_token'] ?? $_SESSION['_csrf'] ?? null;

if (!$provided || !$sessionToken || !hash_equals((string)$sessionToken, (string)$provided)) {
  jerr('CSRF', 403);
}

$action = (string)($data['action'] ?? '');
$id     = (int)($data['id'] ?? 0);

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$u = current_user();
$isAdmin = (($u['role'] ?? '') === 'admin');
$uid = (int)($u['id'] ?? 0);

// Helper: check eigendom/perm
function mustOwnOrAdmin(PDO $pdo, int $id, bool $isAdmin, int $uid): array {
  $st = $pdo->prepare('SELECT id, instructor_id FROM appointments WHERE id = ?');
  $st->execute([$id]);
  $ev = $st->fetch(PDO::FETCH_ASSOC);
  if (!$ev) jerr('Afspraak niet gevonden', 404);
  if (!$isAdmin && (int)$ev['instructor_id'] !== $uid) jerr('Geen rechten', 403);
  return $ev;
}

// Acties die een id vereisen
if (in_array($action, ['update','move','resize','cancel','restore','delete','reschedule','reassign'], true) && $id<=0){
  jerr('Ongeldig id');
}

try {
  switch ($action) {
    case 'move':
    case 'resize': {
      mustOwnOrAdmin($pdo, $id, $isAdmin, $uid);
      $start = new DateTime((string)($data['start'] ?? ''));
      $end   = new DateTime((string)($data['end'] ?? ''));
      if ($end <= $start) jerr('Einde moet na start liggen');
      $q = $pdo->prepare('UPDATE appointments SET start = ?, end = ? WHERE id = ?');
      $q->execute([$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $id]);
      jok();
    }

    case 'update': { // alleen titel + tijden
      mustOwnOrAdmin($pdo, $id, $isAdmin, $uid);
      $title = trim((string)($data['title'] ?? ''));
      if ($title === '') jerr('Titel verplicht');
      $start = new DateTime((string)($data['start'] ?? ''));
      $end   = new DateTime((string)($data['end'] ?? ''));
      if ($end <= $start) jerr('Einde moet na start liggen');
      $q = $pdo->prepare('UPDATE appointments SET title=?, start=?, end=? WHERE id=?');
      $q->execute([$title, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $id]);
      jok();
    }

    case 'reschedule': { // titel + start/eind + (optioneel) andere instructeur
      $ev = mustOwnOrAdmin($pdo, $id, $isAdmin, $uid);
      $title = trim((string)($data['title'] ?? ''));
      if ($title === '') jerr('Titel verplicht');
      $start = new DateTime((string)($data['start'] ?? ''));
      $end   = new DateTime((string)($data['end'] ?? ''));
      if ($end <= $start) jerr('Einde moet na start liggen');

      $instructorId = $data['instructor_id'] ?? null;
      if ($instructorId !== null) {
        $instructorId = (int)$instructorId;
        if (!$isAdmin && $instructorId !== (int)$ev['instructor_id']) {
          // alleen admin mag naar andere instructeur verplaatsen
          jerr('Alleen admin mag naar andere instructeur verplaatsen', 403);
        }
        // bestaat instructeur?
        $chk = $pdo->prepare('SELECT id FROM users WHERE id=? AND role IN ("instructor","admin")');
        $chk->execute([$instructorId]);
        if (!$chk->fetch()) jerr('Onbekende instructeur');
        $q = $pdo->prepare('UPDATE appointments SET title=?, start=?, end=?, instructor_id=? WHERE id=?');
        $q->execute([$title, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $instructorId, $id]);
      } else {
        $q = $pdo->prepare('UPDATE appointments SET title=?, start=?, end=? WHERE id=?');
        $q->execute([$title, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $id]);
      }
      jok();
    }

    case 'reassign': { // alleen instructeur wisselen
      $ev = mustOwnOrAdmin($pdo, $id, $isAdmin, $uid);
      $instructorId = (int)($data['instructor_id'] ?? 0);
      if ($instructorId<=0) jerr('Ongeldige instructeur');
      if (!$isAdmin && $instructorId !== (int)$ev['instructor_id']) jerr('Alleen admin mag naar andere instructeur verplaatsen', 403);
      $chk = $pdo->prepare('SELECT id FROM users WHERE id=? AND role IN ("instructor","admin")');
      $chk->execute([$instructorId]);
      if (!$chk->fetch()) jerr('Onbekende instructeur');
      $q = $pdo->prepare('UPDATE appointments SET instructor_id=? WHERE id=?');
      $q->execute([$instructorId, $id]);
      jok();
    }

    case 'cancel': {
      mustOwnOrAdmin($pdo, $id, $isAdmin, $uid);
      $q = $pdo->prepare('UPDATE appointments SET status = "cancelled" WHERE id = ?');
      $q->execute([$id]); jok();
    }

    case 'restore': {
      mustOwnOrAdmin($pdo, $id, $isAdmin, $uid);
      $q = $pdo->prepare('UPDATE appointments SET status = "planned" WHERE id = ?');
      $q->execute([$id]); jok();
    }

    case 'delete': {
      mustOwnOrAdmin($pdo, $id, $isAdmin, $uid);
      $q = $pdo->prepare('DELETE FROM appointments WHERE id = ?');
      $q->execute([$id]); jok();
    }

    default:
      jerr('Onbekende actie');
  }
} catch (Throwable $e) {
  jerr('Serverfout: '.$e->getMessage(), 500);
}
