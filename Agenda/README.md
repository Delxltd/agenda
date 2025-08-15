# Modern Login Pack

Drop-in, moderne inloginterface. Geen externe CDNs, donker/licht modus, floating labels.

## Bestanden
- `login_modern.php` – complete pagina.
- `assets/auth.css` – stijl.
- `assets/auth.js` – kleine interacties.

## Integratie
1. Plaats de map op je webroot of binnen je project.
2. Zet je eigen logo/naam in de header.
3. Laat het formulier posten naar je bestaande handler:
   - Standaard `action="login.php"`. Pas aan naar `auth.php` of iets anders als nodig.
4. Verwerk CSRF server-side:
   ```php
   // In je handler (login.php of auth.php)
   session_start();
   if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
       $_SESSION['auth_error'] = 'Ongeldige sessie. Probeer opnieuw.';
       header('Location: /login.php'); exit;
   }
   // ... valideer email/wachtwoord ...
   ```
5. Toon fouten door `$_SESSION['auth_error']` te zetten of `?error=...` mee te geven.

## Tips
- Wil je een compactere kaart? Verklein `--radius` en `--space` in `auth.css`.
- Andere kleuren? Pas `--brand` en `--brand-2` aan.
- Respecteert `prefers-reduced-motion`.

Veel succes!
