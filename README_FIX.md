# Modern Login Fix Pack

Wat is dit?
- Nieuwe `auth.php` met veilige login en fallback voor oude hashes (MD5/SHA1/crypt 13).
- Automatische upgrade naar `password_hash()` bij de eerstvolgende succesvolle login.
- Bijpassende moderne `login_modern.php` (UI), plus CSS/JS.

Snel starten
1) Kopieer alle bestanden naar je webroot of dezelfde map als je huidige login.
2) Zorg dat `db.php` een `$pdo` (PDO) object aanmaakt. Zie `db.sample.php` als voorbeeld.
3) Open `auth.php` en check de constante namen bovenaan:
   - `USERS_TABLE`, `COL_EMAIL`, `COL_PASSWORD`, etc. Pas ze aan aan jouw DB schema.
4) Ga naar `/login_modern.php` en test met een oud account. Bij succes wordt het wachtwoord automatisch ge-upgrade.

Opmerking
- De checkbox “Ingelogd blijven” heeft geen tokenimplementatie in deze fix. Dat kan later via remember-tokens.

Problemen?
- Krijg je altijd “Onjuiste gegevens”? Zet tijdelijk logging in `auth.php` bij `$user` en `$hash` of stuur me je huidige kolomnamen.
