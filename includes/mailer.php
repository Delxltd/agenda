<?php
require_once __DIR__ . '/config.php';

/**
 * Eenvoudige mailer met ondersteuning voor 'mail()' en SMTP.
 * SMTP: werkt met SMTPS (implicit TLS op 465) of STARTTLS (587).
 */
function send_mail($to, $subject, $html): bool {
  if (!$GLOBALS['MAIL_ENABLED']) return false;
  if ($GLOBALS['MAIL_TRANSPORT'] === 'smtp') {
    return smtp_send($to, $subject, $html);
  } else {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . mb_encode_mimeheader($GLOBALS['MAIL_FROM_NAME']) . " <{$GLOBALS['MAIL_FROM']}>\r\n";
    $headers .= "Reply-To: {$GLOBALS['MAIL_FROM']}\r\n";
    return mail($to, $subject, $html, $headers);
  }
}

// ---- Minimal SMTP client (AUTH LOGIN/PLAIN) ----
function smtp_expect($fp, $prefixes = ['250','220','235','334','354']) {
  $resp = '';
  while (!feof($fp)) {
    $line = fgets($fp, 515);
    if ($line === false) break;
    $resp .= $line;
    if (preg_match('/^\d{3}\s/', $line)) break;
  }
  foreach ((array)$prefixes as $p) {
    if (strpos($resp, $p) === 0) return $resp;
  }
  throw new Exception("SMTP unexpected response: " . trim($resp));
}

function smtp_send($to, $subject, $html): bool {
  $host = $GLOBALS['SMTP_HOST'];
  $port = (int)$GLOBALS['SMTP_PORT'];
  $enc  = strtolower($GLOBALS['SMTP_ENCRYPTION']);
  $user = $GLOBALS['SMTP_USER'];
  $pass = $GLOBALS['SMTP_PASS'];
  $from = $GLOBALS['MAIL_FROM'];
  $name = $GLOBALS['MAIL_FROM_NAME'];

  $remote = $host . ':' . $port;
  $scheme = ($enc === 'ssl') ? 'ssl://' : '';
  $fp = @stream_socket_client(($scheme ? $scheme : '') . $remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
  if (!$fp) throw new Exception("SMTP connect error: $errstr ($errno)");
  stream_set_timeout($fp, 20);

  smtp_expect($fp, '220');
  fwrite($fp, "EHLO rijschoolagenda.local\r\n"); smtp_expect($fp, '250');

  if ($enc === 'tls') {
    fwrite($fp, "STARTTLS\r\n"); smtp_expect($fp, '220');
    if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
      throw new Exception("Failed to start TLS");
    }
    fwrite($fp, "EHLO rijschoolagenda.local\r\n"); smtp_expect($fp, '250');
  }

  if ($user && $pass) {
    // Try AUTH LOGIN
    fwrite($fp, "AUTH LOGIN\r\n"); $r = smtp_expect($fp, '334');
    fwrite($fp, base64_encode($user) . "\r\n"); smtp_expect($fp, '334');
    fwrite($fp, base64_encode($pass) . "\r\n"); smtp_expect($fp, '235');
  }

  fwrite($fp, "MAIL FROM:<$from>\r\n"); smtp_expect($fp, '250');
  fwrite($fp, "RCPT TO:<$to>\r\n"); smtp_expect($fp, ['250','251']);
  fwrite($fp, "DATA\r\n"); smtp_expect($fp, '354');

  $boundary = 'b'.bin2hex(random_bytes(8));
  $headers  = "From: " . mb_encode_mimeheader($name) . " <{$from}>\r\n";
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
  $headers .= "Reply-To: {$from}\r\n";

  $msg  = $headers;
  $msg .= "Subject: " . mb_encode_mimeheader($subject) . "\r\n";
  $msg .= "To: <$to>\r\n";
  $msg .= "\r\n";
  $msg .= $html . "\r\n";
  $msg .= ".\r\n";
  fwrite($fp, $msg);
  smtp_expect($fp, '250');

  fwrite($fp, "QUIT\r\n");
  fclose($fp);
  return true;
}
