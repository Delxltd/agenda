<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user()) { header('Location: /calendar.php'); }
else { header('Location: /login.php'); }
