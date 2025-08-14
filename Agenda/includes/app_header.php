<?php
// includes/app_header.php
require_once __DIR__ . '/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$u        = $_SESSION['user'] ?? null;
$isLogged = (bool)$u;
$name     = $u['name'] ?? ($u['email'] ?? 'Gebruiker');
$role     = $u['role'] ?? '';

$page_title = $page_title ?? 'Rijschool Agenda';
$nav_active = $nav_active ?? 'agenda';

/**
 * Layout modes:
 * - 'app'  : volledige app met navbar
 * - 'auth' : full-screen auth (login/register) zónder navbar
 */
$layout = $layout ?? 'app';
$GLOBALS['__APP_LAYOUT'] = $layout;
?><!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($page_title) ?></title>

<link rel="stylesheet" href="/assets/app_pro.css">
<style>
:root{
  --brand:#2563eb; --brand-600:#2563eb; --brand-700:#1d4ed8;
  --border:#e5e7eb; --paper:#fff; --bg:#f7f8fb; --text:#111827; --muted:#6b7280;
  --radius:14px; --header:64px;
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:14px/1.45 system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial,sans-serif}

/* NAVBAR (app-layout) */
.nav{position:sticky;top:0;z-index:50;background:var(--paper);height:var(--header);
     border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 16px;gap:12px}
.nav .brand{font-weight:700}
.nav .menu{display:flex;gap:6px;margin-left:12px}
.nav .menu a{padding:8px 12px;border-radius:10px;text-decoration:none;color:inherit}
.nav .menu a.active{background:#eef2ff;color:#1d4ed8}
.nav .sp{flex:1}
.nav .user{display:flex;gap:8px;align-items:center}
.btn{border:1px solid var(--border);background:#fff;padding:8px 12px;border-radius:10px;cursor:pointer;text-decoration:none;color:inherit}

/* CONTAINER (app-layout) */
.container{max-width:1400px;margin:0 auto;padding:16px}
.card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:12px}

/* AUTH-LAYOUT (login) */
.auth-shell{min-height:100svh;display:grid;place-items:center;position:relative;overflow:hidden}
.auth-bg{
  position:absolute;inset:0;background:
    radial-gradient(1200px 600px at -15% -10%, rgba(37,99,235,.08), transparent 60%),
    radial-gradient(1200px 600px at 115% 110%, rgba(14,165,233,.08), transparent 60%),
    linear-gradient(180deg, #f7f8fb 0%, #ffffff 100%);
}
.auth-main{position:relative;width:100%;max-width:420px;padding:24px}
.auth-card{
  background:rgba(255,255,255,.86);
  border:1px solid rgba(226,232,240,.9);
  border-radius:20px;
  box-shadow:0 20px 60px rgba(2,6,23,.08);
  backdrop-filter:saturate(160%) blur(8px);
  padding:24px 22px;
}
.auth-brand{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.auth-brand .dot{width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,var(--brand-600),var(--brand-700))}
.auth-title{font-size:22px;font-weight:800;letter-spacing:.2px}
.auth-sub{font-size:13px;color:var(--muted);margin-top:2px}

.input, .select, .btn-solid, .btn-ghost{
  width:100%;padding:10px 12px;border-radius:12px;outline:none;
  font:inherit;border:1px solid var(--border);background:#fff;
}
.input:focus{border-color:var(--brand-600);box-shadow:0 0 0 3px rgba(37,99,235,.15)}
.btn-solid{background:var(--brand-600);color:#fff;border-color:var(--brand-600);cursor:pointer}
.btn-solid:hover{background:var(--brand-700);border-color:var(--brand-700)}
.btn-ghost{background:#fff;color:var(--text);cursor:pointer}
.row{display:grid;gap:10px}
.help{font-size:12px;color:var(--muted)}
.error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:8px 10px;border-radius:10px;margin-bottom:10px}
.right{display:flex;justify-content:flex-end}
.m-6{margin:6px 0}.mt-6{margin-top:6px}.mt-12{margin-top:12px}.mt-16{margin-top:16px}
</style>
</head>
<body>

<?php if ($layout === 'auth'): ?>
  <div class="auth-shell">
    <div class="auth-bg"></div>
    <main class="auth-main">
<?php else: ?>
  <nav class="nav" role="navigation" aria-label="Hoofdmenu">
    <div class="brand">Rijschool Agenda</div>
    <div class="menu" role="tablist" aria-label="Navigatie">
      <a class="<?= $nav_active==='agenda'?'active':'' ?>" href="/calendar.php">Agenda</a>
      <a class="<?= $nav_active==='students'?'active':'' ?>" href="/students.php">Leerlingen</a>
      <a class="<?= $nav_active==='instructors'?'active':'' ?>" href="/instructors.php">Instructeurs</a>
      <a class="<?= $nav_active==='invoices'?'active':'' ?>" href="/invoices.php">Facturen</a>
      <a class="<?= $nav_active==='reports'?'active':'' ?>" href="/reports_vat.php">BTW & Omzet</a>
    </div>
    <div class="sp"></div>
    <div class="user">
      <?php if ($isLogged): ?>
        <span><?= h($name) ?><?= $role?' · '.strtoupper(h($role)) : '' ?></span>
        <a class="btn" href="/logout.php">Uitloggen</a>
      <?php else: ?>
        <a class="btn" href="/login.php">Inloggen</a>
      <?php endif; ?>
    </div>
  </nav>
  <div class="container">
<?php endif; ?>
