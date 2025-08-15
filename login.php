<?php
require_once __DIR__ . '/includes/auth.php';

/* Uncomment while debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals(csrf_token(), $_POST['csrf'] ?? '')) {
    $error = 'Ongeldige sessie. Probeer opnieuw.';
  } else {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');
    try {
      $u = verify_password_login($email, $pass);
      if ($u) { login_user($u); header('Location: /calendar.php'); exit; }
      $error = 'Onjuiste combinatie van e‑mail en wachtwoord.';
    } catch (Throwable $e) {
      $error = 'Serverfout: ' . h($e->getMessage());
    }
  }
}

$page_title = 'Inloggen';
$nav_active = '';
$layout     = 'auth';
require __DIR__ . '/includes/app_header.php';
?>

<section class="auth-card" role="dialog" aria-labelledby="login-title" aria-describedby="login-desc">
  <div class="auth-brand" aria-hidden="true">
    <div class="dot"></div>
    <div>
      <div class="auth-title" id="login-title">Inloggen</div>
      <div class="auth-sub" id="login-desc">Welkom terug! Log in om je agenda te beheren.</div>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="error" role="alert"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="post" class="row" id="loginForm" novalidate>
    <?= csrf_input() ?>

    <div class="mt-6">
      <label for="email" class="help">Email</label>
      <input id="email" name="email" type="email" class="input" placeholder="jij@voorbeeld.nl" autocomplete="username" required>
    </div>

    <div class="mt-6">
      <label for="password" class="help">Wachtwoord</label>
      <div style="position:relative">
        <input id="password" name="password" type="password" class="input" placeholder="••••••••" autocomplete="current-password" required>
        <button type="button" id="togglePw" class="btn-ghost" style="position:absolute;right:6px;top:6px;width:auto;padding:6px 10px;border-radius:8px">Toon</button>
      </div>
      <div id="capsWarn" class="help" style="display:none;color:#b91c1c;margin-top:6px">Let op: <strong>Caps Lock</strong> staat aan.</div>
    </div>

    <div class="mt-6" style="display:flex;justify-content:space-between;align-items:center">
      <label class="help" style="display:flex;gap:8px;align-items:center">
        <input type="checkbox" id="remember" style="accent-color:var(--brand)"> Onthoud mij
      </label>
      <a class="help" href="#" onclick="alert('Wachtwoord vergeten? Neem contact op met de beheerder.');return false;">Wachtwoord vergeten?</a>
    </div>

    <div class="mt-16">
      <button class="btn-solid" id="btnLogin" style="width:100%">Inloggen</button>
    </div>
  </form>

  <div class="right mt-12">
    <a href="/" class="help">← Terug naar website</a>
  </div>
</section>

<script>
document.getElementById('togglePw').addEventListener('click', function(){
  const pw = document.getElementById('password');
  const is = pw.type === 'password';
  pw.type = is ? 'text' : 'password';
  this.textContent = is ? 'Verberg' : 'Toon';
});
const pw = document.getElementById('password');
pw.addEventListener('keyup', function(e){
  const on = e.getModifierState && e.getModifierState('CapsLock');
  document.getElementById('capsWarn').style.display = on ? 'block' : 'none';
});
document.getElementById('loginForm').addEventListener('submit', function(){
  const btn = document.getElementById('btnLogin');
  btn.disabled = true; btn.textContent = 'Bezig…';
});
</script>

<?php require __DIR__ . '/includes/app_footer.php'; ?>
