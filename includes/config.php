<?php
/**
 * Rijschool Agenda – CONFIG (PRO updates)
 * Plaats in /includes/config.php
 */
date_default_timezone_set('Europe/Amsterdam');

// =====================
// DATABASE (MySQL)
// =====================
$DB_DRIVER = 'mysql';
$DB_HOST   = 'localhost';
$DB_NAME   = 'u142407p436219_new';      // <-- pas aan indien anders
$DB_USER   = 'u142407p436219_new';      // <-- pas aan indien anders
$DB_PASS   = 'QgFJvdysa7D6HrveSyC2';    // <-- pas aan indien anders
$DB_CHARSET = 'utf8mb4';
$DB_DSN    = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHARSET";

/**
 * >>> Belangrijk: db.php van jou verwacht (waarschijnlijk) constants.
 *     Deze mappings zorgen dat zowel variabelen als constants beschikbaar zijn.
 */
if (!defined('DB_HOST')    && isset($DB_HOST))    define('DB_HOST',    $DB_HOST);
if (!defined('DB_NAME')    && isset($DB_NAME))    define('DB_NAME',    $DB_NAME);
if (!defined('DB_USER')    && isset($DB_USER))    define('DB_USER',    $DB_USER);
if (!defined('DB_PASS')    && isset($DB_PASS))    define('DB_PASS',    $DB_PASS);
if (!defined('DB_CHARSET') && isset($DB_CHARSET)) define('DB_CHARSET', $DB_CHARSET);
if (!defined('DB_DSN')     && isset($DB_DSN))     define('DB_DSN',     $DB_DSN);
// optioneel port (alleen als jouw db.php het gebruikt)
if (!defined('DB_PORT') && isset($DB_PORT)) define('DB_PORT', (int)$DB_PORT);

// =====================
// E-MAIL
// =====================
// Zet op false om alle mails uit te zetten
$MAIL_ENABLED    = true;
$MAIL_FROM       = 'noreply@jouwdomein.nl'; // wijzig naar je domein
$MAIL_FROM_NAME  = 'Rijschool Naam';
$MAIL_ADMIN      = 'jij@jouwdomein.nl';

// Transport: 'mail' (PHP mail()) of 'smtp'
$MAIL_TRANSPORT  = 'smtp';

// SMTP settings (alleen nodig als $MAIL_TRANSPORT = 'smtp')
$SMTP_HOST       = 'smtp.jouwdomein.nl';
$SMTP_PORT       = 465;         // 465 = SMTPS (implicit TLS), 587 = STARTTLS
$SMTP_ENCRYPTION = 'ssl';       // 'ssl', 'tls' (STARTTLS) of '' (geen)
$SMTP_USER       = 'smtp-gebruiker@jouwdomein.nl';
$SMTP_PASS       = 'smtp-wachtwoord';

// =====================
// BETALINGEN (stub)
// =====================
$PAYMENTS_ENABLED  = false;
$MOLLIE_API_KEY    = '';
$STRIPE_SECRET_KEY = '';

// =====================
// BTW / TAX
// =====================
$VAT_STANDARD = 0.21;  // Hoog tarief
$VAT_REDUCED  = 0.09;  // Laag tarief ( NL 9% )

// =====================
// ICS / FEED
// =====================
$ICS_PROD_ID  = '-//RijschoolAgenda//NL';
$ICS_TZ       = 'Europe/Amsterdam';

// =====================
// SECURITY / SESSIE / CRON
// =====================
$APP_NAME     = 'Rijschool Agenda';
$SESSION_NAME = 'rijschool_session';
$CRON_TOKEN   = 'changeme-strong-token'; // gebruik deze als ?token=... voor cron scripts

if (session_status() === PHP_SESSION_NONE) {
  session_name($SESSION_NAME);
  session_start();
}
