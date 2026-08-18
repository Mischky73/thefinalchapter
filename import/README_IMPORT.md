# WordPress XML Import – The Final Chapter

Dieses Skript importiert Artikel und Kategorien aus einem WordPress WXR-Export
in die MySQL-Datenbank des Custom-CMS.

---

## Voraussetzungen

| Anforderung | Mindestversion |
|---|---|
| PHP | 8.1+ |
| PHP-Erweiterungen | `pdo_mysql`, `simplexml`, `posix` |
| MySQL / MariaDB | 5.7+ / 10.3+ |

Die Datenbank muss **vor** dem Import eingerichtet sein:

```bash
# Datenbank anlegen (einmalig)
mysql -u root -p -e "CREATE DATABASE thelastchapter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Schema & Demo-Daten einspielen
mysql -u root -p thelastchapter < ../install.sql
```

Zugangsdaten in `includes/config.php` prüfen:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'thelastchapter');
define('DB_USER', 'dein_benutzer');
define('DB_PASS', 'dein_passwort');
```

---

## Verwendung

Alle Befehle werden aus dem Verzeichnis `import/` heraus ausgeführt:

```bash
cd /pfad/zum/projekt/import
```

### Dry-Run (empfohlen als ersten Schritt)

Zeigt, was importiert werden würde – **schreibt nichts** in die Datenbank:

```bash
php import.php --dry-run
```

Mit Begrenzung auf die ersten 20 Artikel:

```bash
php import.php --dry-run --limit=20
```

### Live-Import

```bash
php import.php
```

### Alle Optionen

```
php import.php [OPTIONS]

  --dry-run           Vorschau, keine DB-Schreibvorgänge
  --file=PFAD         Alternativer WXR-Dateipfad
  --limit=N           Nur die ersten N Artikel importieren (0 = alle)
  --skip=N            Erste N Artikel überspringen (z. B. für Fortsetzung)
  --help              Diese Hilfe anzeigen
```

### Beispiele

```bash
# Nur die ersten 10 Artikel importieren (Test)
php import.php --limit=10

# Artikel 101–200 importieren (nach vorherigem Abbruch)
php import.php --skip=100 --limit=100

# Anderen WXR-Datei verwenden
php import.php --file=/tmp/export.xml --dry-run

# Vollständiger Import (alle Artikel)
php import.php
```

---

## Was das Skript importiert

| Feld | Quelle im WXR |
|---|---|
| Titel | `<title>` |
| Slug | `<wp:post_name>` (mit Kollisions-Handling) |
| Inhalt | `<content:encoded>` |
| Kurztext | `<excerpt:encoded>` |
| Autor | `<dc:creator>` |
| Datum | `<pubDate>` → `created_at` / `updated_at` |
| Kategorie | `<category domain="category">` → erste Übereinstimmung |
| Vorschaubild | `_thumbnail_id`-Meta → Attachment-URL; Fallback: erstes `<img>` im Inhalt |
| Status | immer `published` (nur `publish`-Posts werden importiert) |

### Kategorien

- Alle `<wp:category>`-Elemente werden in die Tabelle `categories` übertragen
- Eltern-Kategorien werden zuerst angelegt
- Bereits vorhandene Kategorien (gleicher Slug) werden übersprungen
- Ist für einen Artikel keine Kategorie aus dem XML bekannt, wird die
  Kategorie `news` (oder die erste vorhandene) als Fallback verwendet

### Slug-Kollisionen

Existiert ein Slug bereits (aus einem früheren Import oder dem Demo-Inhalt),
wird automatisch ein Suffix angehängt: `-2`, `-3` usw.

---

## Ausgabe (Beispiel)

```
┌──────────────────────────────────────────────────────────────┐
│          The Final Chapter – WordPress Import v1.0.0         │
└──────────────────────────────────────────────────────────────┘

[INFO] Connecting to database …
[ OK ] Database connection established.
[INFO] Loading XML file: thelastchapter.WordPress.2026-06-12.xml (12,345,678 bytes)
[ OK ] XML loaded successfully.

[INFO] Step 1: Parsing WP categories …
[INFO] Found 29 categories in XML.
[INFO] Step 2: Importing categories …
[ OK ] Category created: Dies & Das (id: 6)
[ OK ] Category created: Liveberichte (id: 7)
...
[INFO] Step 3: Building attachment URL map …
[INFO] Found 8421 attachments.

[INFO] Step 4: Parsing published posts …
[INFO] Found 12140 published posts.

[INFO] Step 5: Importing articles …

  Importing 1 of 12140: SECRETS OF THE MOON beim Party San 2022
  Importing 2 of 12140: …

┌──────────────────────────────────────────────────────────────┐
│  Import Summary                                              │
├──────────────────────────────────────────────────────────────┤
│  Mode              : LIVE                                    │
│  XML file          : thelastchapter.WordPress.2026-06-12.xml │
│  Articles imported : 12140                                   │
│  Categories created: 24                                      │
│  Slug collisions   : 3                                       │
└──────────────────────────────────────────────────────────────┘
```

---

## Fehlerbehebung

### „Database connection failed"

```
[ERR ] Database connection failed: SQLSTATE[HY000] [1045] Access denied …
```

→ Zugangsdaten in `includes/config.php` prüfen und ggf. den MySQL-Nutzer
  mit den nötigen Rechten anlegen:

```sql
CREATE USER 'tlc_user'@'localhost' IDENTIFIED BY 'sicheres_passwort';
GRANT ALL PRIVILEGES ON thelastchapter.* TO 'tlc_user'@'localhost';
FLUSH PRIVILEGES;
```

### „XML file not found"

→ Pfad zur WXR-Datei mit `--file=` angeben oder die Datei in den
  `import/`-Ordner legen.

### Out-of-Memory

Für sehr große XML-Dateien (>100 MB) das PHP-Speicherlimit erhöhen:

```bash
php -d memory_limit=1G import.php
```

### Import unterbrochen fortsetzen

Mit `--skip` und `--limit` lässt sich der Import portionsweise durchführen:

```bash
# Erste 1000 importieren
php import.php --limit=1000

# Nächste 1000 importieren
php import.php --skip=1000 --limit=1000
```

---

## Dateien

```
import/
├── import.php                              ← Dieses Skript
├── README_IMPORT.md                        ← Diese Dokumentation
└── thelastchapter.WordPress.2026-06-12.xml ← WXR-Exportdatei
```

---

## Hinweise

- Ein zweimaliger Import **derselben** Daten ist sicher: Slug-Kollisionen
  werden automatisch aufgelöst (`-2`, `-3` …), es entstehen jedoch
  Duplikate mit geändertem Slug. Es empfiehlt sich daher, den Import
  nach einem Test-Lauf mit `--dry-run` **einmalig** durchzuführen.
- Anhänge (Bilder) werden **nicht** heruntergeladen – nur die originalen
  URLs von `thelastchapter.de` werden übernommen.
- Der Import setzt das Passwort-Hash des Default-Admin-Accounts
  (`redaktion`) **nicht** zurück.
