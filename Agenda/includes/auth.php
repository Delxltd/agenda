<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

/** Safe HTML escape */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** CSRF with robust randomness fallback (older PHP hosts) */
function _rnd_bytes($len = 16){
  if (function_exists('random_bytes'))        return random_bytes($len);
  if (function_exists('openssl_random_pseudo_bytes')) return openssl_random_pseudo_bytes($len);
  // last resort (not cryptographic, but better than nothing)
  $buf = ''; for ($i=0; $i<$len; $i++) { $buf .= chr(mt_rand(0,255)); } return $buf;
}
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(_rnd_bytes(16));
  return $_SESSION['csrf'];
}
function csrf_input(): string { return '<input type="hidden" name="csrf" value="'.h(csrf_token()).'">'; }

/** Session helpers */
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void {
  if (!current_user()) { header('Location: /login.php'); exit; }
}
function login_user(array $u): void { $_SESSION['user'] = $u; }
function logout_user(): void { $_SESSION = []; session_destroy(); }

/** Users */
function find_user_by_email(string $email): ?array {
  $st = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
  $st->execute([$email]);
  return $st->fetch() ?: null;
}

/**
 * Verify password for a user by email with backwards-compat:
 * - supports columns: password_hash (bcrypt/argon2), password (legacy hash/plain)
 * - migrates to PASSWORD_DEFAULT on successful legacy login
 * Returns user row on success, null on failure.
 */
function verify_password_login(string $email, string $plain): ?array {
  $u = find_user_by_email($email);
  if (!$u) return null;

  $hash = $u['password_hash'] ?? ($u['password'] ?? '');
  $ok = false;

  if (is_string($hash) && $hash !== '') {
    if (strpos($hash, '$2y$') === 0 || strpos($hash, '$argon2') === 0) {
      // Modern hash
      $ok = password_verify($plain, $hash);
    } else {
      // Possibly md5/sha1/plain
      if (strlen($hash) === 32 && ctype_xdigit($hash)) {
        $ok = (md5($plain) === strtolower($hash));
      } else if (strlen($hash) === 40 && ctype_xdigit($hash)) {
        $ok = (sha1($plain) === strtolower($hash));
      } else {
        // assume plaintext
        $ok = hash_equals($hash, $plain);
      }
      // Migrate to strong hash on success
      if ($ok) {
        $new = password_hash($plain, PASSWORD_DEFAULT);
        try {
          // Prefer password_hash column
          $st = db()->prepare("UPDATE users SET password_hash=? WHERE id=?");
          $st->execute([$new, $u['id']]);
        } catch (Throwable $e) {
          // Fallback to legacy column if that's all there is
          try { $st = db()->prepare("UPDATE users SET password=? WHERE id=?"); $st->execute([$new, $u['id']]); } catch (Throwable $e2) {}
        }
        // refresh row with new hash (optional)
        $u = find_user_by_email($email) ?: $u;
      }
    }
  }

  return $ok ? $u : null;
}

/** Convenience role check */
function check_role(string $role): bool {
  $u = current_user(); if (!$u) return false;
  return ($u['role'] ?? '') === $role;
}
