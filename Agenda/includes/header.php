<?php
require_once __DIR__.'/helpers.php';
require_once __DIR__.'/auth.php';
$labels = appt_labels();
$u = current_user();
$page = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isCalendar = ($page === 'calendar.php');
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Rijschool Agenda</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/theme.css">
  <style>body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-900 <?= $isCalendar ? 'is-calendar' : '' ?>">
<header id="appHeader" class="app-header fixed top-0 inset-x-0 bg-white/95 backdrop-blur border-b z-50">
  <nav class="mx-auto max-w-[1200px] px-4">
    <div class="h-14 flex items-center justify-between">
      <a class="flex items-center gap-2" href="/calendar.php">
        <span class="inline-flex h-7 w-7 rounded bg-blue-600"></span>
        <span class="font-semibold tracking-tight">Rijschool Agenda</span>
      </a>
      <ul class="hidden md:flex items-center gap-6 text-sm">
        <?php if ($u): ?>
          <li><a class="nav-link<?= $page==='calendar.php'?' is-active':'' ?>" href="/calendar.php">Agenda</a></li>
          <li><a class="nav-link<?= $page==='students.php'?' is-active':'' ?>" href="/students.php">Leerlingen</a></li>
          <li><a class="nav-link<?= $page==='invoices.php'?' is-active':'' ?>" href="/invoices.php">Facturen</a></li>
          <li><a class="nav-link<?= $page==='reports_vat.php'?' is-active':'' ?>" href="/reports_vat.php">BTW &amp; Omzet</a></li>
        <?php else: ?>
          <li><a class="nav-link<?= $page==='book.php'?' is-active':'' ?>" href="/book.php">Publiek boeken</a></li>
          <li><a class="nav-link<?= $page==='login.php'?' is-active':'' ?>" href="/login.php">Inloggen</a></li>
        <?php endif; ?>
      </ul>
      <div class="flex items-center gap-3">
        <?php if ($u): ?>
          <div class="hidden md:flex items-center gap-2 text-sm text-gray-600">
            <span class="truncate max-w-[160px]"><?= h($u['name']) ?></span>
            <span class="text-gray-400">•</span>
            <span class="uppercase"><?= h($u['role']) ?></span>
          </div>
          <a href="/logout.php" class="btn btn-subtle hidden md:inline-flex">Uitloggen</a>
        <?php endif; ?>
        <button id="navToggle" class="md:hidden btn btn-subtle" aria-label="menu">☰</button>
      </div>
    </div>
  </nav>
  <div id="mobileMenu" class="md:hidden hidden border-t bg-white">
    <div class="px-4 py-2 flex flex-col gap-2 text-sm">
      <?php if ($u): ?>
        <a class="nav-link<?= $page==='calendar.php'?' is-active':'' ?>" href="/calendar.php">Agenda</a>
        <a class="nav-link<?= $page==='students.php'?' is-active':'' ?>" href="/students.php">Leerlingen</a>
        <a class="nav-link<?= $page==='invoices.php'?' is-active':'' ?>" href="/invoices.php">Facturen</a>
        <a class="nav-link<?= $page==='reports_vat.php'?' is-active':'' ?>" href="/reports_vat.php">BTW &amp; Omzet</a>
        <a class="nav-link" href="/logout.php">Uitloggen</a>
      <?php else: ?>
        <a class="nav-link<?= $page==='book.php'?' is-active':'' ?>" href="/book.php">Publiek boeken</a>
        <a class="nav-link<?= $page==='login.php'?' is-active':'' ?>" href="/login.php">Inloggen</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="app-main">
  <?php foreach (flashes() as $f): ?>
    <div class="mx-auto max-w-[1200px] px-4">
      <div class="flash <?= $f['t']==='success'?'flash-success':($f['t']==='error'?'flash-error':'flash-info') ?>"><?= h($f['m']) ?></div>
    </div>
  <?php endforeach; ?>
<script>
  document.addEventListener('DOMContentLoaded', function(){
    const header = document.getElementById('appHeader');
    const root = document.documentElement;
    function setH(){ root.style.setProperty('--header-h', header.offsetHeight + 'px'); }
    setH(); window.addEventListener('resize', setH);
    const t = document.getElementById('navToggle'), m = document.getElementById('mobileMenu');
    if (t) t.addEventListener('click', ()=> m.classList.toggle('hidden'));
  });
</script>
