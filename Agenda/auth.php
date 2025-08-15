<?php
declare(strict_types=1);
session_start();

/**
 * Rijschool Agenda — auth handler
 * - gebruikt $pdo uit db.php
 * - legacy hash fallback (MD5/SHA1/crypt 13) + automatische upgrade
 * - zet sessies die de agenda gebruikt
 * - redirect naar calendar.php
 */

const LOGIN_PAGE   = 'login.php';
const AFTER_LOGIN  = 'calendar.php';

// Pas aan naar jouw schema indien nodig:
const USERS_TABLE  = 'users';
const COL_ID       = 'id';
const COL_EMAIL    = 'email';
const COL_PASSWORD = 'password';
const COL_NAME     = 'name';        // zet op null als niet aanwezig
const COL_ROLE     = 'role';        // zet op null als niet aanwezig
const COL_ACTIVE   = 'is_active';   // zet op null als niet aanwezig
const COL_DELETED  = 'deleted_at';  // zet op null als niet aanwezig

require_once __DIR__ . '/db.php'; // moet $pdo (PDO) opleveren
if (!isset($pdo) || !($pdo instanceof PDO)) { http_response_code(500); echo "DB niet geconfigureerd (db.php)."; exit; }
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(LOGIN_PAGE);

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) fail('Sessie verlopen. Probeer opnieuw.');
if (($_POST['intent'] ?? '') !== 'login') fail('Ongeldige aanvraag.');

$email = trim((string)($_POST['email'] ?? ''));
$pass  = (string)($_POST['password'] ?? '');
if ($email === '' || $pass === '') fail('Vul e‑mail en wachtwoord in.');
usleep(250_000);

// dynamische kolommen
$cols = [COL_ID." AS id", COL_EMAIL." AS email", COL_PASSWORD." AS password"];
if (COL_NAME)   $cols[] = COL_NAME." AS name";
if (COL_ROLE)   $cols[] = COL_ROLE." AS role";
if (COL_ACTIVE) $cols[] = COL_ACTIVE." AS is_active";
if (COL_DELETED)$cols[] = COL_DELETED." AS deleted_at";

$sql = "SELECT ".implode(", ", $cols)." FROM ".USERS_TABLE." WHERE ".COL_EMAIL." = :email LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':email'=>$email]);
$user = $stmt->fetch();
if (!$user) fail('Onjuiste gegevens.');
if (array_key_exists('is_active',$user) && $user['is_active'] !== null && (int)$user['is_active'] === 0) fail('Account is inactief.');
if (array_key_exists('deleted_at',$user) && $user['deleted_at']) fail('Account is uitgeschakeld.');

$hash = (string)$user['password'];
$ok = false;
$info = password_get_info($hash);
if (($info['algo'] ?? 0) !== 0) {
  $ok = password_verify($pass, $hash);
  if ($ok && password_needs_rehash($hash, PASSWORD_DEFAULT)) {
    upgradeUserHash($pdo, (int)$user['id'], password_hash($pass, PASSWORD_DEFAULT));
  }
}
if (!$ok && verifyLegacy($pass, $hash)) {
  $ok = true;
  upgradeUserHash($pdo, (int)$user['id'], password_hash($pass, PASSWORD_DEFAULT));
}
if (!$ok) fail('Onjuiste gegevens.');

// sessies die de agenda kan gebruiken
session_regenerate_id(true);
$_SESSION['user_id']    = (int)$user['id'];
$_SESSION['user_email'] = (string)$user['email'];
if (array_key_exists('name',$user)) $_SESSION['user_name'] = (string)$user['name'];
if (array_key_exists('role',$user)) $_SESSION['role'] = (string)$user['role'];
// backward compat
$_SESSION['uid'] = $_SESSION['user_id'];

unset($_SESSION['csrf_token']);
header('Location: ' . AFTER_LOGIN);
exit;

function fail(string $m): void { $_SESSION['auth_error']=$m; header('Location: '.LOGIN_PAGE); exit; }
function redirect(string $to): void { header('Location: '.$to); exit; }
function upgradeUserHash(PDO $pdo, int $id, string $new): void {
  $sql = "UPDATE ".USERS_TABLE." SET ".COL_PASSWORD." = :new WHERE ".COL_ID." = :id";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':new'=>$new, ':id'=>$id]);
}
function verifyLegacy(string $password, string $hash): bool {
  if (preg_match('/^[a-f0-9]{32}\z/i',$hash)) return hash_equals($hash, md5($password));
  if (preg_match('/^[a-f0-9]{40}\z/i',$hash)) return hash_equals($hash, sha1($password));
  if (strlen($hash) === 13) return hash_equals($hash, crypt($password, $hash));
  return false;
}
