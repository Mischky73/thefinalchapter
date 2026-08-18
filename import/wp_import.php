<?php
/**
 * WordPress XML → The Final Chapter CMS Import
 * 
 * Verwendung:
 *   php import/wp_import.php
 * 
 * Importiert: Kategorien (mit Hierarchie), Artikel (post_type=post, status=publish)
 * Bilder werden NICHT heruntergeladen - nur URLs gespeichert (featured_image)
 */

define('WP_XML',   __DIR__ . '/thelastchapter.WordPress.2026-06-12.xml');
define('DB_HOST',  'localhost');
define('DB_NAME',  'thefinalchapter');
define('DB_USER',  'root');
define('DB_PASS',  '');
define('DRY_RUN',  false); // auf true setzen zum Testen ohne DB-Schreibzugriff

// ── Verbindung ────────────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✓ DB-Verbindung OK\n";
} catch (PDOException $e) {
    die("✗ DB-Verbindung fehlgeschlagen: " . $e->getMessage() . "\n");
}

// ── XML laden (Streaming für große Dateien) ────────────────────────────────────
echo "→ Lade XML (" . round(filesize(WP_XML) / 1048576, 1) . " MB)...\n";

// Kaputte CDATA-Blöcke bereinigen (WP-Export hat manchmal doppelte ]]>)
// Wir nutzen XMLReader für Stream-Verarbeitung
$xml = new XMLReader();
if (!$xml->open(WP_XML)) {
    die("✗ XML-Datei nicht lesbar: " . WP_XML . "\n");
}

// ── Kategorien sammeln ────────────────────────────────────────────────────────
echo "→ Lese Kategorien...\n";
$cats_raw = []; // slug => [name, parent_slug, description]

// SimpleXML für den Header-Teil (Kategorien kommen vor den Items)
$header_xml = '';
$handle = fopen(WP_XML, 'r');
$in_channel = false;
$buffer = '';
$item_started = false;
$bytes_read = 0;
$max_header = 2 * 1024 * 1024; // Erste 2MB sollten alle Kategorien enthalten

while (!feof($handle) && $bytes_read < $max_header) {
    $line = fgets($handle, 8192);
    $bytes_read += strlen($line);
    if (strpos($line, '<item>') !== false) break;
    $buffer .= $line;
}
fclose($handle);

// Kategorien mit Regex extrahieren (robuster als XML bei WP-Exports)
preg_match_all(
    '/<wp:category>(.*?)<\/wp:category>/s',
    $buffer,
    $cat_matches
);

foreach ($cat_matches[1] as $cat_block) {
    $id     = preg_match('/<wp:term_id>(\d+)<\/wp:term_id>/', $cat_block, $m) ? (int)$m[1] : 0;
    $slug   = cdata_val($cat_block, 'wp:category_nicename');
    $name   = html_entity_decode(cdata_val($cat_block, 'wp:cat_name'), ENT_XML1, 'UTF-8');
    $parent = cdata_val($cat_block, 'wp:category_parent');
    $desc   = cdata_val($cat_block, 'wp:category_description');
    if ($slug && $name) {
        $cats_raw[$slug] = ['id' => $id, 'name' => $name, 'parent_slug' => $parent, 'description' => $desc];
    }
}
echo "  Gefunden: " . count($cats_raw) . " Kategorien\n";

// ── Kategorien in DB einfügen (erst Eltern, dann Kinder) ─────────────────────
$slug_to_db_id = []; // slug → neue DB-ID

if (!DRY_RUN) {
    // Bestehende Kategorien leeren
    $pdo->exec("DELETE FROM categories");
    $pdo->exec("ALTER TABLE categories AUTO_INCREMENT = 1");

    $stmt_cat = $pdo->prepare(
        "INSERT INTO categories (name, slug, parent_id, description) VALUES (?, ?, ?, ?)"
    );

    // Eltern zuerst
    foreach ($cats_raw as $slug => $c) {
        if (empty($c['parent_slug'])) {
            $stmt_cat->execute([$c['name'], $slug, null, $c['description']]);
            $slug_to_db_id[$slug] = (int)$pdo->lastInsertId();
        }
    }
    // Dann Kinder
    foreach ($cats_raw as $slug => $c) {
        if (!empty($c['parent_slug'])) {
            $parent_id = $slug_to_db_id[$c['parent_slug']] ?? null;
            $stmt_cat->execute([$c['name'], $slug, $parent_id, $c['description']]);
            $slug_to_db_id[$slug] = (int)$pdo->lastInsertId();
        }
    }
    echo "  ✓ " . count($slug_to_db_id) . " Kategorien importiert\n";
} else {
    echo "  [DRY_RUN] würde " . count($cats_raw) . " Kategorien einfügen\n";
}

// ── Artikel importieren (Streaming via XMLReader) ─────────────────────────────
echo "→ Importiere Artikel (nur post_type=post, status=publish)...\n";

$stmt_art = DRY_RUN ? null : $pdo->prepare("
    INSERT INTO articles
        (title, slug, content, excerpt, category_id, author, featured_image, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'published', ?)
    ON DUPLICATE KEY UPDATE
        content=VALUES(content), title=VALUES(title), excerpt=VALUES(excerpt),
        category_id=VALUES(category_id), featured_image=VALUES(featured_image)
");

$count_ok = 0;
$count_skip = 0;
$count_err = 0;

// Wir lesen Item-Blöcke mit Regex (robuster als XMLReader bei WP CDATA)
$handle = fopen(WP_XML, 'r');
$item_buffer = '';
$in_item = false;
$total_items = 0;

while (!feof($handle)) {
    $line = fgets($handle, 8192);
    
    if (strpos($line, '<item>') !== false) {
        $in_item = true;
        $item_buffer = $line;
        continue;
    }
    if ($in_item) {
        $item_buffer .= $line;
        if (strpos($line, '</item>') !== false) {
            $in_item = false;
            $total_items++;
            
            // Post-Type prüfen
            if (!preg_match('/<wp:post_type><!\[CDATA\[post\]\]><\/wp:post_type>/', $item_buffer) &&
                !preg_match('/<wp:post_type>post<\/wp:post_type>/', $item_buffer)) {
                $count_skip++;
                $item_buffer = '';
                continue;
            }
            // Status prüfen
            if (!preg_match('/<wp:status><!\[CDATA\[publish\]\]><\/wp:status>/', $item_buffer) &&
                !preg_match('/<wp:status>publish<\/wp:status>/', $item_buffer)) {
                $count_skip++;
                $item_buffer = '';
                continue;
            }
            
            // Felder extrahieren
            $title   = decode_wp(wp_field($item_buffer, 'title'));
            $slug    = cdata_val($item_buffer, 'wp:post_name') ?: slugify($title);
            $content = decode_wp(cdata_val($item_buffer, 'content:encoded'));
            $excerpt = decode_wp(cdata_val($item_buffer, 'excerpt:encoded'));
            $author  = decode_wp(cdata_val($item_buffer, 'dc:creator')) ?: 'Michael Jakob';
            $date    = cdata_val($item_buffer, 'wp:post_date') ?: date('Y-m-d H:i:s');
            
            // Kategorie
            $cat_slug = '';
            if (preg_match('/<category domain="category" nicename="([^"]+)"/', $item_buffer, $m)) {
                $cat_slug = $m[1];
            }
            $cat_id = $slug_to_db_id[$cat_slug] ?? null;
            
            // Featured Image (aus wp:postmeta _thumbnail_id → wir speichern nur die erste Bild-URL aus dem Content)
            $featured_image = '';
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $content, $m)) {
                $featured_image = $m[1];
            }
            
            if (!$title || !$slug) {
                $count_err++;
                $item_buffer = '';
                continue;
            }
            
            if (!DRY_RUN) {
                try {
                    $stmt_art->execute([
                        mb_substr($title, 0, 500),
                        mb_substr($slug, 0, 255),
                        $content,
                        mb_substr($excerpt, 0, 1000),
                        $cat_id,
                        mb_substr($author, 0, 100),
                        mb_substr($featured_image, 0, 500),
                        $date
                    ]);
                    $count_ok++;
                } catch (PDOException $e) {
                    $count_err++;
                    if ($count_err <= 3) {
                        echo "  ✗ Fehler bei '$title': " . $e->getMessage() . "\n";
                    }
                }
            } else {
                $count_ok++;
            }
            
            // Fortschritt alle 500 Artikel
            if ($count_ok % 500 === 0) {
                echo "  → " . $count_ok . " Artikel importiert...\n";
                flush();
            }
            
            $item_buffer = '';
        }
    }
}
fclose($handle);

echo "\n=== Import abgeschlossen ===\n";
echo "  Importiert:  $count_ok Artikel\n";
echo "  Übersprungen: $count_skip (Entwürfe, Seiten, etc.)\n";
echo "  Fehler:       $count_err\n";
echo "  Gesamt Items: $total_items\n";

if (!DRY_RUN) {
    $total = $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
    $cats  = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    echo "\n  DB-Stand: $total Artikel, $cats Kategorien\n";
}
echo "\n✓ Fertig!\n";

// ── Hilfsfunktionen ───────────────────────────────────────────────────────────

function cdata_val(string $xml, string $tag): string {
    if (preg_match('/<' . preg_quote($tag, '/') . '>(?:<!\[CDATA\[)?(.*?)(?:\]\]>)?<\/' . preg_quote($tag, '/') . '>/s', $xml, $m)) {
        return trim($m[1]);
    }
    return '';
}

function wp_field(string $xml, string $tag): string {
    // Für einfache Felder ohne Namespace
    if (preg_match('/<' . preg_quote($tag, '/') . '>(?:<!\[CDATA\[)?(.*?)(?:\]\]>)?<\/' . preg_quote($tag, '/') . '>/s', $xml, $m)) {
        return trim($m[1]);
    }
    return '';
}

function decode_wp(string $s): string {
    return html_entity_decode($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function slugify(string $s): string {
    $s = mb_strtolower($s);
    $s = preg_replace('/[äÄ]/u', 'ae', $s);
    $s = preg_replace('/[öÖ]/u', 'oe', $s);
    $s = preg_replace('/[üÜ]/u', 'ue', $s);
    $s = preg_replace('/[ß]/u', 'ss', $s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}
