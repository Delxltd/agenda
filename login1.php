<?php
require __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/auth.php';
if (current_user()) { header('Location: /calendar.php'); exit; }
verify_csrf();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $pw = $_POST['password'] ?? '';
  $next = $_POST['next'] ?? '/calendar.php';
  $user = find_user_by_email($email);
  if ($user && password_verify($pw, $user['password_hash'])) {
    login_user($user); flash('Welkom terug, '.$user['name'], 'success'); header('Location: '.$next); exit;
  }
  flash('Onjuist e‑mailadres of wachtwoord', 'error');
}
$next = $_GET['next'] ?? '/calendar.php';
?>
<div class="max-w-md mx-auto">
  <div class="card p-6">
    <h1 class="text-2xl font-semibold mb-1">Inloggen</h1>
    <p class="text-sm opacity-70 mb-4">Toegang tot de beheeragenda</p>
    <form method="post" action="/login.php" class="space-y-3">
      <?= csrf_input() ?>
      <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">
      <div>
        <label class="block text-sm mb-1 opacity-70">E‑mail</label>
        <input type="email" name="email" class="input" required autofocus>
      </div>
      <div>
        <label class="block text-sm mb-1 opacity-70">Wachtwoord</label>
        <input type="password" name="password" class="input" required>
      </div>
      <button class="btn btn-primary w-full">Inloggen</button>
    </form>
  </div>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
