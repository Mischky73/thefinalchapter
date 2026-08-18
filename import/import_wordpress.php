<?php
/**
 * WordPress XML → The Final Chapter CMS Import
 * 
 * Verwendung: php import/import_wordpress.php [--dry-run] [--limit=100]
 * 
 * --dry-run   : Nur analysieren, nichts in DB schreiben
 * --limit=N   : Nur die ersten N Artikel importieren (zum Testen)
 */

set_time_limit(0);
ini_set('memory_limit', '512M');

// ── Konfiguration ─────────────────────────────────────────────────
define('XML_FILE', __DIR__ . '/thelastchapter.WordPress.2026-06-12.xml');
define('DB_HOST', 'localhost');
define('DB_NAME', 'thefinalchapter');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', 'https://thefinalchapter.de');  // für Bildpfad-Ersetzung

// ── CLI Argumente ─────────────────────────────────────────────────
$dry_run = in_array('--dry-run', $argv);
$limit   = 0;
foreach ($argv as $arg) {
    if (preg_match('/--limit=(\d+)/', $arg, $m)) {
        $limit = (int)$m[1];
    }
}

echo "=== The Final Chapter – WordPress Import ===\n";
echo "XML:      " . XML_FILE . "\n";
echo "Dry-Run:  " . ($dry_run ? 'JA' : 'NEIN') . "\n";
echo "Limit:    " . ($limit ?: 'kein Limit') . "\n\n";

if (!file_exists(XML_FILE)) {
    die("❌ XML-Datei nicht gefunden: " . XML_FILE . "\n");
}

// ── DB Verbindung ─────────────────────────────────────────────────
if (!$dry_run) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "✅ Datenbankverbindung OK\n\n";
    } catch (PDOException $e) {
        die("❌ DB-Fehler: " . $e->getMessage() . "\n");
    }
}

// ── XML laden ─────────────────────────────────────────────────────
echo "📂 Lade XML... (kann einen Moment dauern)\n";
$xml = simplexml_load_file(XML_FILE, 'SimpleXMLElement', LIBXML_NOCDATA);
if (!$xml) {
    die("❌ XML konnte nicht gelesen werden.\n");
}
echo "✅ XML geladen\n\n";

$ns_wp      = $xml->channel->children('wp', true);
$ns_content = 'http://purl.org/rss/1.0/modules/content/';
$ns_dc      = 'http://purl.org/dc/elements/1.1/';
$ns_excerpt = 'http://wordpress.org/export/1.2/excerpt/';

// ── 1. Kategorien importieren ─────────────────────────────────────
echo "📁 Importiere Kategorien...\n";

$cat_map  = []; // nicename → neue DB-ID
$cat_data = []; // alle Kategorien aus XML, erst sammeln dann hierarchisch einfügen

foreach ($xml->channel->children('wp', true)->category as $cat) {
    $nicename   = (string)$cat->category_nicename;
    $name       = html_entity_decode((string)$cat->cat_name);
    $parent     = (string)$cat->category_parent;
    $desc       = (string)$cat->category_description;
    $cat_data[] = compact('nicename', 'name', 'parent', 'desc');
}

// Mehrere Durchläufe bis alle Parents aufgelöst sind
$rounds = 0;
$remaining = $cat_data;
while (!empty($remaining) && $rounds < 10) {
    $rounds++;
    $next = [];
    foreach ($remaining as $c) {
        // Parent muss bereits existieren (oder leer sein)
        if ($c['parent'] !== '' && !isset($cat_map[$c['parent']])) {
            $next[] = $c;
            continue;
        }
        $parent_id = $c['parent'] ? ($cat_map[$c['parent']] ?? 0) : 0;
        if (!$dry_run) {
            // Prüfen ob schon vorhanden
            $slug = $c['nicename'];
            $row  = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
            $row->execute([$slug]);
            $existing = $row->fetchColumn();
            if ($existing) {
                $cat_map[$c['nicename']] = $existing;
                continue;
            }
            $stmt = $pdo->prepare(
                "INSERT INTO categories (name, slug, parent_id, description) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$c['name'], $slug, $parent_id, $c['desc']]);
            $cat_map[$c['nicename']] = $pdo->lastInsertId();
        } else {
            $cat_map[$c['nicename']] = mt_rand(100, 9999);
        }
        echo "  ✓ Kategorie: {$c['name']}" . ($c['parent'] ? " (unter: {$c['parent']})" : "") . "\n";
    }
    $remaining = $next;
}

if (!empty($remaining)) {
    echo "  ⚠️  " . count($remaining) . " Kategorien konnten nicht aufgelöst werden (unbekannte Parents).\n";
}
echo "✅ " . count($cat_map) . " Kategorien importiert\n\n";

// ── 2. Artikel importieren ────────────────────────────────────────
echo "📰 Importiere Artikel...\n";

$count_ok   = 0;
$count_skip = 0;
$count_err  = 0;
$i          = 0;

if (!$dry_run) {
    $stmt = $pdo->prepare("
        INSERT INTO articles 
        (title, slug, content, excerpt, category_id, author, featured_image, status, created_at, updated_at)
        VALUES 
        (:title, :slug, :content, :excerpt, :category_id, :author, :featured_image, :status, :created_at, :updated_at)
        ON DUPLICATE KEY UPDATE
        title         = VALUES(title),
        content       = VALUES(content),
        excerpt       = VALUES(excerpt),
        category_id   = VALUES(category_id),
        featured_image= VALUES(featured_image),
        updated_at    = VALUES(updated_at)
    ");
}

foreach ($xml->channel->item as $item) {
    $post_type = (string)$item->children('wp', true)->post_type;
    $status    = (string)$item->children('wp', true)->status;

    // Nur echte Posts importieren
    if ($post_type !== 'post') continue;
    if (!in_array($status, ['publish', 'draft'])) { $count_skip++; continue; }

    $i++;
    if ($limit && $i > $limit) break;

    $title    = html_entity_decode((string)$item->title);
    $slug     = (string)$item->children('wp', true)->post_name;
    $content  = (string)$item->children($ns_content)->encoded;
    $excerpt  = (string)$item->children($ns_excerpt)->encoded;
    $author   = (string)$item->children($ns_dc)->creator;
    $pub_date = (string)$item->children('wp', true)->post_date;
    $pub_date = $pub_date ?: date('Y-m-d H:i:s');

    // Kategorie zuordnen (erste Kategorie nehmen)
    $cat_id = 0;
    foreach ($item->category as $c) {
        $domain   = (string)$c['domain'];
        $nicename = (string)$c['nicename'];
        if ($domain === 'category' && isset($cat_map[$nicename])) {
            $cat_id = $cat_map[$nicename];
            break;
        }
    }

    // Featured Image (aus wp:postmeta)
    $featured_image = '';
    // (Bilder liegen auf thefinalchapter.de, wir speichern die alte URL erstmal)
    foreach ($item->children('wp', true)->postmeta as $meta) {
        if ((string)$meta->meta_key === '_thumbnail_id') {
            // Wir speichern nur die ID fürs Erste, Bilder werden später migriert
            break;
        }
    }

    // Slug bereinigen
    if (!$slug) {
        $slug = sanitizeSlug($title);
    }
    $slug = substr($slug, 0, 200);

    if (!$title || !$slug) { $count_skip++; continue; }

    if (!$dry_run) {
        try {
            $stmt->execute([
                ':title'          => $title,
                ':slug'           => $slug,
                ':content'        => $content,
                ':excerpt'        => strip_tags($excerpt ?: mb_substr($content, 0, 300)),
                ':category_id'    => $cat_id ?: null,
                ':author'         => $author,
                ':featured_image' => $featured_image,
                ':status'         => $status === 'publish' ? 'published' : 'draft',
                ':created_at'     => $pub_date,
                ':updated_at'     => $pub_date,
            ]);
            $count_ok++;
        } catch (PDOException $e) {
            echo "  ⚠️  Fehler bei '$title': " . $e->getMessage() . "\n";
            $count_err++;
        }
    } else {
        $count_ok++;
    }

    // Fortschritt alle 500 Artikel
    if ($count_ok % 500 === 0) {
        echo "  … {$count_ok} Artikel importiert\n";
    }
}

// ── Zusammenfassung ───────────────────────────────────────────────
echo "\n=== FERTIG ===\n";
echo "✅ Importiert:   $count_ok Artikel\n";
echo "⏭️  Übersprungen: $count_skip (kein Post oder kein Publish/Draft-Status)\n";
if ($count_err) echo "❌ Fehler:       $count_err\n";
echo "\n";

if ($dry_run) {
    echo "ℹ️  Das war ein Dry-Run – nichts wurde gespeichert.\n";
    echo "    Starte ohne --dry-run für den echten Import.\n";
} else {
    echo "🎉 Import abgeschlossen!\n";
}

// ── Hilfsfunktionen ───────────────────────────────────────────────
function sanitizeSlug(string $title): string {
    $slug = mb_strtolower($title);
    $slug = str_replace(['ä','ö','ü','ß'], ['ae','oe','ue','ss'], $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'artikel-' . time();
}
