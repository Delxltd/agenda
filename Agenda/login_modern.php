<?php
declare(strict_types=1);
session_start();

$error = $_GET['error'] ?? ($_SESSION['auth_error'] ?? null);
unset($_SESSION['auth_error']);

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
?><!doctype html>
<html lang="nl" class="no-js">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <title>Inloggen</title>
  <link rel="stylesheet" href="assets/auth.css?v=1.0">
  <script>document.documentElement.classList.remove('no-js');document.documentElement.classList.add('js');</script>
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Ccircle cx='32' cy='32' r='30' fill='%23008cff'/%3E%3Ctext x='32' y='39' text-anchor='middle' font-size='28' font-family='system-ui' fill='white'%3E%F0%9F%94%92%3C/text%3E%3C/svg%3E">
</head>
<body>
  <div class="bg">
    <div class="bg-gradient"></div>
    <div class="bg-blob one"></div>
    <div class="bg-blob two"></div>
    <div class="bg-blob three"></div>
  </div>

  <main class="auth-wrapper" role="main">
    <section class="auth-card" aria-labelledby="login-title">
      <header class="auth-header">
        <div class="brand">
          <svg class="brand-mark" viewBox="0 0 32 32" aria-hidden="true">
            <defs>
              <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" />
                <stop offset="1" />
              </linearGradient>
            </defs>
            <rect x="1" y="1" width="30" height="30" rx="7" ry="7" fill="url(#g)"/>
            <path d="M10 22 L16 10 L22 22 Z" fill="currentColor" opacity="0.9"/>
          </svg>
          <div class="brand-text">
            <span class="brand-title">Jouw&nbsp;App</span>
            <span class="brand-subtitle">Beveiligde toegang</span>
          </div>
        </div>
        <h1 id="login-title" class="visually-hidden">Inloggen</h1>
      </header>

      <?php if (!empty($error)): ?>
        <div class="alert" role="alert">
          <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l10 18H2L12 2zm0 5a1 1 0 00-1 1v6a1 1 0 002 0V8a1 1 0 00-1-1zm0 10a1.25 1.25 0 100 2.5A1.25 1.25 0 0012 17z"></path></svg>
          <div class="alert-text"><?php echo htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php endif; ?>

      <form class="auth-form" method="post" action="auth.php" autocomplete="on" spellcheck="false" novalidate>
        <input type="hidden" name="intent" value="login">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

        <div class="field">
          <input id="email" name="email" type="email" inputmode="email" required placeholder=" "
                 autocomplete="email" aria-describedby="email-help">
          <label for="email">E-mailadres</label>
          <small id="email-help" class="help">Bijv. naam@bedrijf.nl</small>
        </div>

        <div class="field with-addon">
          <input id="password" name="password" type="password" required minlength="6" placeholder=" "
                 autocomplete="current-password">
          <label for="password">Wachtwoord</label>
          <button class="addon" type="button" aria-label="Toon wachtwoord" data-action="toggle-visibility" data-target="#password">
            <svg class="i" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 11a4 4 0 110-8 4 4 0 010 8z"/>
            </svg>
          </button>
        </div>

        <div class="row">
          <label class="switch">
            <input type="checkbox" name="remember" value="1">
            <span class="slider"></span>
            <span class="switch-label">Ingelogd blijven</span>
          </label>
          <a class="link" href="forgot.php">Wachtwoord vergeten?</a>
        </div>

        <button class="btn primary" type="submit">Inloggen</button>
      </form>

      <footer class="auth-footer">
        <p>Geen account? <a class="link" href="register.php">Maak er één aan</a>.</p>
      </footer>
    </section>

    <p class="legal">Door in te loggen ga je akkoord met onze <a href="/terms">voorwaarden</a> en <a href="/privacy">privacyverklaring</a>.</p>
  </main>

  <script src="assets/auth.js?v=1.0" defer></script>
</body>
</html>
