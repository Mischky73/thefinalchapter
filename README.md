# The Final Chapter – CMS

Selbst entwickeltes CMS für das Heavy Metal Internetmagazin *The Final Chapter* aus Südthüringen.

**Stack:** PHP 8+ / MySQL / Hostinger-kompatibel  
**Kein WordPress** – schlankes, eigenes System

---

## Installation (Hostinger / lokaler Server)

### 1. Datenbank erstellen

Im Hostinger-Panel oder phpMyAdmin eine neue MySQL-Datenbank anlegen, z.B.:
- Datenbankname: `thefinalchapter`
- Benutzer + Passwort notieren

Dann das Schema einspielen:

```bash
mysql -u BENUTZER -p DATENBANKNAME < install.sql
```

Oder in phpMyAdmin: → Datei importieren → `install.sql` auswählen

### 2. Konfiguration anpassen

`includes/config.php` öffnen und anpassen:

```php
define('DB_HOST', 'localhost');       // bei Hostinger oft: localhost
define('DB_NAME', 'thefinalchapter');  // dein Datenbankname
define('DB_USER', 'dein_benutzer');
define('DB_PASS', 'dein_passwort');

define('SITE_URL', 'https://thefinalchapter.press'); // deine Domain
```

### 3. Dateien hochladen

Alle Dateien per FTP/SFTP oder Hostinger File Manager in das `public_html/`-Verzeichnis hochladen.

### 4. Login

Lege das erste Admin-Konto mit individuellen Zugangsdaten und einem per
`password_hash()` erzeugten Passwort-Hash an. Das Installationsschema enthält
bewusst kein Standardkonto. Öffne danach `/admin/`.

---

## Admin-Bereich

URL: `/admin/`

| Seite | Funktion |
|-------|----------|
| Dashboard | Statistiken, Schnellzugang |
| Artikel | Alle Artikel verwalten, bearbeiten, löschen |
| Neu schreiben | Neuen Artikel anlegen |
| Kategorien | Kategorien anlegen/bearbeiten |

---

## Struktur

```
thefinalchapter/
├── index.php              Startseite
├── article.php            Einzelartikel
├── category.php           Kategorie-Übersicht
├── kontakt.php            Kontaktseite
├── install.sql            Datenbankschema + Demo-Daten
├── admin/
│   ├── login.php          Login
│   ├── logout.php
│   ├── index.php          Dashboard
│   ├── articles.php       Artikel-Liste
│   ├── article_edit.php   Artikel anlegen/bearbeiten
│   ├── article_delete.php Artikel löschen
│   └── categories.php     Kategorien verwalten
├── includes/
│   ├── config.php         Konfiguration
│   ├── db.php             Datenbankverbindung (PDO)
│   ├── auth.php           Session/Login
│   └── functions.php      Alle Datenbankabfragen
└── assets/
    ├── css/style.css      Dark Theme
    └── js/main.js         JS (Slug-Generator, Mobile-Nav)
```

---

## Nächste Schritte (Phase 2)

- [ ] WYSIWYG-Editor (TinyMCE) für Artikel
- [ ] Bildupload direkt im Backend
- [ ] Kommentarfunktion
- [ ] Suche
- [ ] .htaccess SEO-URLs (`/news/artikel-slug`)
- [ ] WordPress-Inhalte importieren (XML-Import)
- [ ] Mehrere Redakteure / Benutzer-Verwaltung

---

## Passwort ändern (CLI)

```php
// PHP-Snippet zum Generieren eines neuen Passwort-Hashes:
echo password_hash('NeuesPasswort123!', PASSWORD_BCRYPT);
// Dann in DB: UPDATE users SET password='...' WHERE username='redaktion';
```

---

*The Final Chapter – Internetmag aus Südthüringen seit 2003* 🤘
