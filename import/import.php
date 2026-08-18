<?php
// ============================================================
// WordPress XML Import für The Final Chapter
// Aufruf: php import/import.php
// ============================================================

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$xmlFile  = __DIR__ . '/thelastchapter.WordPress.2026-06-12.xml';
$imgDir   = __DIR__ . '/../assets/img/uploads/';

if (!file_exists($xmlFile)) {
    die("XML-Datei nicht gefunden: $xmlFile\n");
}

// Upload-Verzeichnis anlegen
if (!is_dir($imgDir)) {
    mkdir($imgDir, 0755, true);
}

$db = getDB();

// Kategorien-Cache
$catCache = [];
$res = $db->query("SELECT id, name, slug FROM categories");
if ($res) {
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $catCache[strtolower($row['name'])] = $row['id'];
        $catCache[strtolower($row['slug'])] = $row['id'];
    }
}

function getOrCreateCategory(PDO $db, string $name, array &$cache): int {
    $key  = strtolower(trim($name));
    if (isset($cache[$key])) return $cache[$key];
    $slug = preg_replace('/[^a-z0-9]+/', '-', $key);
    $slug = trim($slug, '-') ?: 'kategorie';
    $stmt = $db->prepare("INSERT IGNORE INTO categories (name, slug) VALUES (?, ?)");
    $stmt->execute([trim($name), $slug]);
    $id = (int)$db->lastInsertId();
    if (!$id) {
        $s = $db->prepare("SELECT id FROM categories WHERE slug = ?");
        $s->execute([$slug]);
        $id = (int)$s->fetchColumn();
    }
    $cache[$key]  = $id;
    $cache[$slug] = $id;
    return $id ?: 1;
}

function makeSlug(string $title, int $wpId): string {
    $slug = strtolower(trim($title));
    $slug = str_replace(
        ['ä','ö','ü','ß','Ä','Ö','Ü'],
        ['ae','oe','ue','ss','ae','oe','ue'],
        $slug
    );
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    $slug = substr($slug, 0, 200);
    return $slug ?: 'post-' . $wpId;
}

function downloadImage(string $url, string $imgDir): string {
    if (empty($url)) return '';
    $filename = basename(parse_url($url, PHP_URL_PATH));
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    if (empty($filename) || $filename === '_') return '';
    $localPath = $imgDir . $filename;
    // Nicht nochmal laden wenn schon vorhanden
    if (file_exists($localPath)) {
        return 'assets/img/uploads/' . $filename;
    }
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data && strlen($data) > 500) {
        file_put_contents($localPath, $data);
        return 'assets/img/uploads/' . $filename;
    }
    return '';
}

$insertStmt = $db->prepare("
    INSERT IGNORE INTO articles
        (title, slug, content, excerpt, category_id, author, featured_image, status, created_at, updated_at, wp_post_id)
    VALUES
        (:title, :slug, :content, :excerpt, :cat, :author, :img, :status, :created, :updated, :wpid)
");

$imported  = 0;
$skipped   = 0;
$errors    = 0;
$imgCount  = 0;
$startTime = time();

echo "Starte Import aus: $xmlFile\n";
echo str_repeat('-', 60) . "\n";

$reader = new XMLReader();
if (!$reader->open($xmlFile)) {
    die("Konnte XML nicht öffnen.\n");
}

while ($reader->read()) {
    if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'item') {

        // readOuterXml NUR EINMAL aufrufen!
        $rawXml = $reader->readOuterXml();

        // Namespaces einfügen
        $xmlStr = preg_replace(
            '/<item(\s[^>]*)?>/',
            '<item xmlns:wp="http://wordpress.org/export/1.2/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:dc="http://purl.org/dc/elements/1.1/">',
            $rawXml,
            1
        );

        $xml = @simplexml_load_string($xmlStr);
        if (!$xml) {
            $errors++;
            $reader->next();
            continue;
        }

        $wpNs     = $xml->children('http://wordpress.org/export/1.2/');
        $postType = trim((string)$wpNs->post_type);
        $status   = trim((string)$wpNs->status);

        // Nur echte Posts
        if ($postType !== 'post') {
            $reader->next();
            continue;
        }
        if (!in_array($status, ['publish', 'draft'])) {
            $reader->next();
            continue;
        }

        $title = trim((string)$xml->title);
        $wpId  = (int)$wpNs->post_id;
        $slug  = trim((string)$wpNs->post_name);
        $date  = trim((string)$wpNs->post_date);

        $contentNs = $xml->children('http://purl.org/rss/1.0/modules/content/');
        $content   = trim((string)$contentNs->encoded);

        $excerptNs = $xml->children('http://wordpress.org/export/1.2/excerpt/');
        $excerpt   = trim((string)$excerptNs->encoded);

        $dcNs   = $xml->children('http://purl.org/dc/elements/1.1/');
        $author = trim((string)$dcNs->creator) ?: 'Redaktion';

        // Featured Image aus postmeta lesen
        $featuredImageUrl = '';
        foreach ($wpNs->postmeta as $meta) {
            $metaKey   = trim((string)$meta->meta_key);
            $metaValue = trim((string)$meta->meta_value);
            if ($metaKey === '_wp_attached_file' || $metaKey === '_thumbnail_id') {
                // Wir suchen nach der echten URL später
            }
        }
        // Erstes Bild aus dem Content extrahieren als Fallback
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $content, $m)) {
            $featuredImageUrl = $m[1];
        }
        // WordPress featured image URL aus postmeta
        foreach ($wpNs->postmeta as $meta) {
            $metaKey   = trim((string)$meta->meta_key);
            $metaValue = trim((string)$meta->meta_value);
            if ($metaKey === '_wp_attachment_metadata' && !empty($metaValue)) {
                // nicht direkt nutzbar ohne attachment lookup
            }
        }

        // Kategorien
        $cats = [];
        foreach ($xml->category as $cat) {
            $domain = (string)$cat->attributes()->domain;
            if ($domain === 'category' || $domain === '') {
                $cats[] = trim((string)$cat);
            }
        }
        $catId = 1;
        if (!empty($cats)) {
            $catId = getOrCreateCategory($db, $cats[0], $catCache);
        }

        $slug     = $slug ?: makeSlug($title, $wpId);
        $date     = $date ?: date('Y-m-d H:i:s');
        $dbStatus = $status === 'publish' ? 'published' : 'draft';

        // Bild herunterladen
        $localImg = '';
        if (!empty($featuredImageUrl)) {
            $localImg = downloadImage($featuredImageUrl, $imgDir);
            if ($localImg) $imgCount++;
        }

        try {
            $insertStmt->execute([
                ':title'   => mb_substr($title, 0, 255),
                ':slug'    => $slug,
                ':content' => $content,
                ':excerpt' => mb_substr($excerpt, 0, 1000),
                ':cat'     => $catId,
                ':author'  => mb_substr($author, 0, 120),
                ':img'     => $localImg,
                ':status'  => $dbStatus,
                ':created' => $date,
                ':updated' => $date,
                ':wpid'    => $wpId ?: null,
            ]);
            if ($insertStmt->rowCount() > 0) {
                $imported++;
            } else {
                $skipped++;
            }
        } catch (PDOException $e) {
            $errors++;
            if ($errors <= 5) echo "  FEHLER: " . $e->getMessage() . "\n";
        }

        // Fortschritt alle 500
        $total = $imported + $skipped + $errors;
        if ($total > 0 && $total % 500 === 0) {
            $elapsed = time() - $startTime;
            echo "  [{$elapsed}s] Verarbeitet: $total | Importiert: $imported | Bilder: $imgCount | Fehler: $errors\n";
            flush();
        }

        $reader->next();
    }
}

$reader->close();

$elapsed = time() - $startTime;
echo str_repeat('-', 60) . "\n";
echo "Import abgeschlossen!\n";
echo "  Importiert  : $imported\n";
echo "  Bilder      : $imgCount\n";
echo "  Übersprungen: $skipped (Duplikate)\n";
echo "  Fehler      : $errors\n";
echo "  Dauer       : {$elapsed} Sekunden\n";
