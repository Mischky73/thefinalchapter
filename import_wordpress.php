<?php
/**
 * The Final Chapter – WordPress XML Import
 * 
 * Liest den WordPress WXR-Export (XML) ein und importiert
 * alle Artikel, Kategorien und Tags in die TLC-Datenbank.
 * 
 * Aufruf:
 *   php import_wordpress.php wordpress-export.xml
 * 
 * Oder mit Testlauf (kein Schreiben in DB):
 *   php import_wordpress.php wordpress-export.xml --dry-run
 */

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

// ──────────────────────────────────────────────
// CLI-Argumente
// ──────────────────────────────────────────────
$xmlFile = $argv[1] ?? null;
$dryRun  = in_array('--dry-run', $argv);

if (!$xmlFile) {
    echo "Aufruf: php import_wordpress.php <wordpress-export.xml> [--dry-run]\n";
    exit(1);
}
if (!file_exists($xmlFile)) {
    echo "Datei nicht gefunden: $xmlFile\n";
    exit(1);
}

echo "=======================================================\n";
echo "  The Final Chapter – WordPress Import\n";
if ($dryRun) echo "  *** TESTLAUF – nichts wird gespeichert ***\n";
echo "=======================================================\n\n";

// ──────────────────────────────────────────────
// XML laden
// ──────────────────────────────────────────────
echo "Lade XML-Datei: $xmlFile ...\n";
libxml_use_internal_errors(true);
$xml = simplexml_load_file($xmlFile, 'SimpleXMLElement', LIBXML_NOCDATA);

if (!$xml) {
    foreach (libxml_get_errors() as $e) echo "XML-Fehler: " . $e->message;
    exit(1);
}

// WordPress-Namespaces registrieren
$ns = $xml->getNamespaces(true);

$channel = $xml->channel;

// ──────────────────────────────────────────────
// 1. Kategorien importieren
// ──────────────────────────────────────────────
echo "\n[1/3] Kategorien importieren...\n";

$catMap   = []; // wp_slug => new_id
$catSlugs = [];

// Alle Kategorien aus der XML sammeln
foreach ($channel->children($ns['wp'] ?? 'wp') as $tag => $node) {
    if ($tag === 'category') {
        $slug   = (string)$node->children($ns['wp'])->cat_name;
        $nicename = (string)$node->children($ns['wp'])->category_nicename;
        $parent = (string)$node->children($ns['wp'])->category_parent;
        $catSlugs[$nicename] = [
            'name'       => $slug,
            'slug'       => $nicename,
            'parent_slug'=> $parent,
        ];
    }
}

// Kategorien ohne Parent zuerst eintragen
$inserted = 0;
foreach ($catSlugs as $slug => $cat) {
    if (!empty($cat['parent_slug'])) continue;
    if ($dryRun) {
        echo "  [DRY] Kategorie: {$cat['name']} ({$cat['slug']})\n";
        $catMap[$slug] = rand(100, 999);
    } else {
        $id = upsertCategory($cat['name'], $cat['slug'], 0, '');
        $catMap[$slug] = $id;
    }
    $inserted++;
}
// Dann Sub-Kategorien
foreach ($catSlugs as $slug => $cat) {
    if (empty($cat['parent_slug'])) continue;
    $parentId = $catMap[$cat['parent_slug']] ?? 0;
    if ($dryRun) {
        echo "  [DRY] Sub-Kategorie: {$cat['name']} (parent: {$cat['parent_slug']})\n";
        $catMap[$slug] = rand(100, 999);
    } else {
        $id = upsertCategory($cat['name'], $cat['slug'], $parentId, '');
        $catMap[$slug] = $id;
    }
    $inserted++;
}

echo "  → $inserted Kategorien verarbeitet\n";

// ──────────────────────────────────────────────
// 2. Artikel importieren
// ──────────────────────────────────────────────
echo "\n[2/3] Artikel importieren...\n";

$items    = $channel->item ?? [];
$total    = count((array)$items);
$done     = 0;
$skipped  = 0;
$errors   = 0;

foreach ($items as $item) {
    $wp = $item->children($ns['wp'] ?? 'wp');

    // Nur Beiträge (post_type = post), keine Seiten, Anhänge etc.
    $postType = (string)$wp->post_type;
    if ($postType !== 'post') { $skipped++; continue; }

    // Status: nur 'publish' importieren (kein Entwurf-Spam)
    $status = (string)$wp->status;
    $tlcStatus = ($status === 'publish') ? 'published' : 'draft';

    // Felder
    $title   = html_entity_decode((string)$item->title, ENT_QUOTES, 'UTF-8');
    $content = (string)$item->children('http://purl.org/rss/1.0/modules/content/');
    $excerpt = (string)$item->children($ns['excerpt'] ?? 'excerpt');
    $slug    = (string)$wp->post_name;
    $author  = (string)$item->children('http://purl.org/dc/elements/1.1/');

    // Datum
    $pubDate = (string)$wp->post_date;
    $created = $pubDate ? date('Y-m-d H:i:s', strtotime($pubDate)) : date('Y-m-d H:i:s');

    // Featured Image (wird über post meta gesetzt)
    $featuredImage = '';
    foreach ($wp->postmeta as $meta) {
        if ((string)$meta->meta_key === '_thumbnail_id') {
            $featuredImage = ''; // Image-URL Lookup müsste über Anhänge erfolgen
        }
    }

    // Kategorie – erste gefundene
    $catId = 1; // Fallback: erste Kategorie
    foreach ($item->category as $cat) {
        $domain   = (string)$cat->attributes()->domain;
        $nicename = (string)$cat->attributes()->nicename;
        if ($domain === 'category' && isset($catMap[$nicename])) {
            $catId = $catMap[$nicename];
            break;
        }
    }

    if (empty($slug)) {
        $slug = slugify($title);
    }

    if ($dryRun) {
        echo "  [DRY] '$title' ({$tlcStatus}, Kat: $catId)\n";
        $done++;
        continue;
    }

    $data = [
        'title'          => $title,
        'slug'           => $slug,
        'content'        => $content,
        'excerpt'        => $excerpt ?: mb_substr(strip_tags($content), 0, 250),
        'category_id'    => $catId,
        'author'         => $author ?: 'Redaktion',
        'featured_image' => $featuredImage,
        'status'         => $tlcStatus,
        'created_at'     => $created,
    ];

    try {
        importArticle($data);
        $done++;
    } catch (Exception $e) {
        // Duplikat-Slug: anhängen und nochmal
        try {
            $data['slug'] = $slug . '-' . substr(md5($title), 0, 6);
            importArticle($data);
            $done++;
        } catch (Exception $e2) {
            echo "  FEHLER: '$title' – " . $e2->getMessage() . "\n";
            $errors++;
        }
    }

    // Fortschritt alle 100 Artikel
    if (($done + $skipped) % 100 === 0) {
        echo "  ... " . ($done + $skipped) . "/$total verarbeitet ($done importiert)\n";
    }
}

echo "  → $done Artikel importiert, $skipped übersprungen, $errors Fehler\n";

// ──────────────────────────────────────────────
// 3. Zusammenfassung
// ──────────────────────────────────────────────
echo "\n[3/3] Zusammenfassung\n";
echo "=======================================================\n";
if ($dryRun) {
    echo "  TESTLAUF abgeschlossen – keine Daten wurden gespeichert\n";
    echo "  Zum echten Import: php import_wordpress.php $xmlFile\n";
} else {
    echo "  ✅ Import abgeschlossen!\n";
    echo "  Kategorien: " . count($catMap) . "\n";
    echo "  Artikel:    $done\n";
    echo "  Übersprungen (Seiten, Anhänge etc.): $skipped\n";
    echo "  Fehler: $errors\n";
}
echo "=======================================================\n";


// ──────────────────────────────────────────────
// Hilfsfunktionen
// ──────────────────────────────────────────────

function slugify(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = str_replace(['ä','ö','ü','ß'], ['ae','oe','ue','ss'], $text);
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

function upsertCategory(string $name, string $slug, int $parentId, string $desc): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    $existing = $stmt->fetchColumn();
    if ($existing) return (int)$existing;

    $db->prepare("INSERT INTO categories (name, slug, parent_id, description) VALUES (?,?,?,?)")
       ->execute([$name, $slug, $parentId, $desc]);
    return (int)$db->lastInsertId();
}

function importArticle(array $data): void {
    $db = getDB();

    $cols = ['title','slug','content','excerpt','category_id','author','featured_image','status','created_at'];
    $placeholders = implode(',', array_fill(0, count($cols), '?'));
    $colList = implode(',', $cols);

    $values = array_map(fn($c) => $data[$c] ?? '', $cols);

    $db->prepare("INSERT INTO articles ($colList) VALUES ($placeholders)")
       ->execute($values);
}
