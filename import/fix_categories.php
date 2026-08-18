<?php
// ============================================================
// Kategorien-Fix: Liest WP-XML erneut und updated category_id
// Aufruf: php import/fix_categories.php
// ============================================================

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$xmlFile = __DIR__ . '/thelastchapter.WordPress.2026-06-12.xml';

if (!file_exists($xmlFile)) {
    die("XML-Datei nicht gefunden: $xmlFile\n");
}

$db = getDB();

// Kategorie-Mapping aus der WP XML (nicename => anzeigename)
$wpCatMap = [
    'news'           => 'News',
    'psoa-news'      => 'PSOA-News',
    'festivals'      => 'Festivals',
    'psan'           => 'PSAN',
    'liveberichte'   => 'Liveberichte',
    'wff-news'       => 'WFF-News',
    'rev'            => 'Reviews',
    'ragnroeck'      => 'Ragnroeck',
    'wod'            => 'WOD',
    'in-flammen-news'=> 'In Flammen News',
    'cds'            => 'CDs',
    'uncategorized'  => 'Allgemein',
];

// Kategorien-Cache aufbauen / anlegen
$catCache = [];
$res = $db->query("SELECT id, slug FROM categories");
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    $catCache[$row['slug']] = (int)$row['id'];
}

function getOrCreateCat(PDO $db, string $slug, string $name, array &$cache): int {
    if (isset($cache[$slug])) return $cache[$slug];
    $stmt = $db->prepare("INSERT IGNORE INTO categories (name, slug) VALUES (?, ?)");
    $stmt->execute([$name, $slug]);
    $id = (int)$db->lastInsertId();
    if (!$id) {
        $s = $db->prepare("SELECT id FROM categories WHERE slug = ?");
        $s->execute([$slug]);
        $id = (int)$s->fetchColumn();
    }
    $cache[$slug] = $id;
    return $id ?: 1;
}

// Sicherstellen dass alle WP-Kategorien angelegt sind
foreach ($wpCatMap as $slug => $name) {
    getOrCreateCat($db, $slug, $name, $catCache);
}

echo "Kategorien angelegt/gecacht: " . count($catCache) . "\n";

// Update-Statement
$updateStmt = $db->prepare("UPDATE articles SET category_id = ? WHERE wp_post_id = ?");

$updated   = 0;
$skipped   = 0;
$errors    = 0;
$startTime = time();

echo "Lese XML und update Kategorien...\n";
echo str_repeat('-', 60) . "\n";

$reader = new XMLReader();
if (!$reader->open($xmlFile)) {
    die("Konnte XML nicht öffnen.\n");
}

while ($reader->read()) {
    if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'item') continue;

    $rawXml = $reader->readOuterXml();
    $xmlStr = preg_replace(
        '/<item(\s[^>]*)?>/',
        '<item xmlns:wp="http://wordpress.org/export/1.2/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:dc="http://purl.org/dc/elements/1.1/">',
        $rawXml, 1
    );

    $xml = @simplexml_load_string($xmlStr);
    if (!$xml) { $errors++; $reader->next(); continue; }

    $wpNs     = $xml->children('http://wordpress.org/export/1.2/');
    $postType = trim((string)$wpNs->post_type);
    $status   = trim((string)$wpNs->status);

    if ($postType !== 'post' || !in_array($status, ['publish', 'draft'])) {
        $reader->next(); continue;
    }

    $wpId = (int)$wpNs->post_id;
    if (!$wpId) { $reader->next(); continue; }

    // Kategorie aus XML lesen
    $catSlug = 'uncategorized';
    $catName = 'Allgemein';
    foreach ($xml->category as $cat) {
        $domain = (string)$cat->attributes()->domain;
        $nicename = (string)$cat->attributes()->nicename;
        if ($domain === 'category' && $nicename !== 'uncategorized') {
            $catSlug = $nicename;
            $catName = isset($wpCatMap[$nicename]) ? $wpCatMap[$nicename] : ucfirst(str_replace('-', ' ', $nicename));
            break;
        }
    }
    // Fallback: uncategorized nehmen wenn nix anderes da
    if ($catSlug === 'uncategorized') {
        foreach ($xml->category as $cat) {
            $domain = (string)$cat->attributes()->domain;
            if ($domain === 'category') {
                $catSlug = (string)$cat->attributes()->nicename;
                $catName = isset($wpCatMap[$catSlug]) ? $wpCatMap[$catSlug] : ucfirst(str_replace('-', ' ', $catSlug));
                break;
            }
        }
    }

    $catId = getOrCreateCat($db, $catSlug, $catName, $catCache);

    try {
        $updateStmt->execute([$catId, $wpId]);
        if ($updateStmt->rowCount() > 0) {
            $updated++;
        } else {
            $skipped++; // Artikel nicht in DB (war kein Import)
        }
    } catch (PDOException $e) {
        $errors++;
    }

    $total = $updated + $skipped + $errors;
    if ($total > 0 && $total % 1000 === 0) {
        $elapsed = time() - $startTime;
        echo "  [{$elapsed}s] Verarbeitet: $total | Aktualisiert: $updated\n";
        flush();
    }

    $reader->next();
}

$reader->close();

$elapsed = time() - $startTime;
echo str_repeat('-', 60) . "\n";
echo "Fertig!\n";
echo "  Aktualisiert: $updated\n";
echo "  Nicht in DB : $skipped\n";
echo "  Fehler      : $errors\n";
echo "  Dauer       : {$elapsed} Sekunden\n";
echo "\nKategorien in der DB:\n";
$res = $db->query("SELECT c.name, c.slug, COUNT(a.id) as anzahl FROM categories c LEFT JOIN articles a ON a.category_id = c.id GROUP BY c.id ORDER BY anzahl DESC");
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    echo "  {$row['name']} ({$row['slug']}): {$row['anzahl']} Artikel\n";
}
