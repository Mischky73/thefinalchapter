# Deployment auf Hostinger – Schritt für Schritt

## 1. Hostinger-Account einrichten

1. Auf [hostinger.de](https://hostinger.de) einloggen
2. **Domain** `thefinalchapter.press` verbinden
3. Im hPanel → **Datenbanken** → neue MySQL-Datenbank anlegen
   - Datenbankname notieren (z.B. `u123456_tfc`)
   - Benutzername + Passwort notieren

---

## 2. Konfiguration anpassen

```bash
# Produktiv-Config aktivieren
cp includes/config.hostinger.php includes/config.php
```

Dann in `includes/config.php` die DB-Zugangsdaten aus Schritt 1 eintragen:

```php
define('DB_NAME', 'u123456_tfc');     // ← dein DB-Name
define('DB_USER', 'u123456_tfc');     // ← dein DB-Benutzer
define('DB_PASS', 'DEIN_PASSWORT');   // ← dein DB-Passwort
```

---

## 3. Datenbank einrichten

Im Hostinger hPanel → **phpMyAdmin** öffnen → `install.sql` importieren:

```
phpMyAdmin → Datei importieren → install.sql auswählen → OK
```

---

## 4. Dateien hochladen

Per **FTP/SFTP** (Zugangsdaten im hPanel unter "FTP-Konten"):

```bash
# Beispiel mit lftp:
lftp -u FTPBENUTZER,FTPPASSWORT ftp.hostinger.com
cd public_html
mirror -R /home/michael/projects/thefinalchapter/ .
```

Oder einfach den **Hostinger File Manager** verwenden.

> ⚠️ Die Datei `includes/config.php` mit deinen echten Zugangsdaten
> **NICHT** per Git pushen!

---

## 5. WordPress-Import (12.140 Artikel)

Nach dem Upload auf dem Server:

```bash
php import/import.php
```

Vorher mit Dry-Run testen:

```bash
php import/import.php --dry-run --limit=50
```

---

## 6. Login

Der Adminbereich liegt unter `/admin/`. Zugangsdaten werden nicht im
Projekt oder in Deployment-Dokumenten hinterlegt.

---

## Checkliste

- [ ] Hostinger-Account erstellt
- [ ] Domain `thefinalchapter.press` verbunden
- [ ] MySQL-Datenbank angelegt
- [ ] `config.php` angepasst
- [ ] `install.sql` importiert
- [ ] Dateien hochgeladen
- [ ] Import durchgeführt
- [ ] Individuelles Admin-Konto vorhanden
- [ ] SSL-Zertifikat aktiv (Let's Encrypt, kostenlos im hPanel)
