# PRO Updates pakket

In dit pakket:
- **SMTP e-mail** ondersteuning (`includes/mailer.php` + `includes/config.php`)
- **Agenda API met periodefilter** (`api/events.php` met `start`/`end`)
- **ICS-feed** (`/calendar_feed.php`) — abonneren in Google/Apple
- **Automatische e-mailreminders** (24u & 2u) via `cron/send_reminders.php`
- **BTW 9% configureerbaar** (config + migratie SQL)
- **SQL updates** voor reminders/indexen

## Installatie
1. **Backup** je site & database.
2. Upload/bestands-override:
   - `includes/config.php` (pas aan waar nodig)
   - `includes/mailer.php`
   - `api/events.php`
   - `calendar_feed.php`
   - `cron/send_reminders.php`
   - `sql/updates/*` (voor referentie)
3. **Voer SQL updates uit** in phpMyAdmin:
   - `sql/updates/2025-08-pro-updates.sql`
   - (optioneel) `sql/updates/migrate_vat_6_to_9.sql` als je 6% nog gebruikt.
4. **Config SMTP** in `includes/config.php`:
   ```php
   $MAIL_TRANSPORT  = 'smtp';
   $SMTP_HOST       = 'smtp.jouwdomein.nl';
   $SMTP_PORT       = 465;         // 465=ssl, 587=tls
   $SMTP_ENCRYPTION = 'ssl';       // of 'tls'
   $SMTP_USER       = 'user@jouwdomein.nl';
   $SMTP_PASS       = 'wachtwoord';
   $MAIL_FROM       = 'noreply@jouwdomein.nl';
   $MAIL_FROM_NAME  = 'Rijschool Naam';
   $MAIL_ADMIN      = 'jij@jouwdomein.nl';
   ```
5. **Cron instellen** (elk uur):
   - Via CLI: `php /pad/naar/site/cron/send_reminders.php`
   - Of via URL (met token): `https://jouwdomein.nl/cron/send_reminders.php?token=CHANGEME`
   - Zet in `includes/config.php` je eigen `$CRON_TOKEN`.

## ICS-feed gebruiken
- Alle instructeurs: `https://jouwdomein.nl/calendar_feed.php`
- Specifieke instructeur: `https://jouwdomein.nl/calendar_feed.php?instructor_id=2`
- Optionele periode: `&from=2025-08-01&to=2025-08-31`
Abonneer in Google Calendar: **Instellingen → Agenda toevoegen → Via URL**.

## Let op
- Deze SMTP‑implementatie is lichtgewicht en werkt met de meeste shared hosters. Bij issues: controleer poort/encryptie en inlog. SPF/DKIM records op je domein verhogen aflevering.
- Voor SMS/WhatsApp‑reminders kun je later Twilio/WhatsApp Business integreren.

Succes! 🎉
