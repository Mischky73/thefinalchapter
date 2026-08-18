<?php
/**
 * The Final Chapter – Vollständiger WordPress XML Re-Import
 * – Kategorien aus XML lesen und anlegen
 * – Artikel den richtigen Kategorien zuordnen
 * – Featured Images von thelastchapter.de herunterladen
 */

ini_set('memory_limit', '512M');
set_time_limit(0);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = getDB();
$xml_file = __DIR__ . '/thelastchapter.WordPress.2026-06-12.xml';
$img_dir  = __DIR__ . '/../assets/img/uploads/';

if (!file_exists($xml_file)) {
    die("XML-Datei nicht gefunden: $xml_file\n");
}
if (!is_dir($img_dir)) {
    mkdir($img_dir, 0755, true);
}

echo "Lade XML... (kann einen Moment dauern)\n";
$xml = simplexml_load_file($xml_file, 'SimpleXMLElement', LIBXML_NOCDATA);
if (!$xml) {
    die("XML konnte nicht geladen werden.\n");
}

$wp = $xml->channel->children('wp', true);

// ── Schritt 1: Kategorien aus XML anlegen ────────────────────────────────────
echo "\nSchritt 1: Kategorien anlegen...\n";

$cat_map = []; // nicename => id

// Direkte <wp:category> Einträge aus dem Header
foreach ($xml->channel->children('wp', true) as $node_name => $node) {
    if ($node_name !== 'category') continue;
    $nicename = (string)$node->category_nicename;
    $name     = (string)$node->cat_name;
    if (!$nicename || !$name) continue;

    // Prüfen ob schon vorhanden
    $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ?");
    $stmt->execute([$nicename]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $cat_map[$nicename] = $row['id'];
    } else {
        $stmt2 = $db->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
        $stmt2->execute([$name, $nicename]);
        $cat_map[$nicename] = $db->lastInsertId();
        echo "  + Kategorie: $name ($nicename)\n";
    }
}

// Auch alle categories die in items vorkommen einlesen
foreach ($xml->channel->item as $item) {
    foreach ($item->category as $cat) {
        $domain   = (string)$cat['domain'];
        $nicename = (string)$cat['nicename'];
        $name     = (string)$cat;
        if ($domain !== 'category' || !$nicename || isset($cat_map[$nicename])) continue;

        $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->execute([$nicename]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $cat_map[$nicename] = $row['id'];
        } else {
            $stmt2 = $db->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
            $stmt2->execute([$name, $nicename]);
            $cat_map[$nicename] = $db->lastInsertId();
            echo "  + Kategorie: $name ($nicename)\n";
        }
    }
}

echo "  Kategorien gesamt: " . count($cat_map) . "\n";

// ── Schritt 2: Attachment-Map aufbauen (post_id => url) ──────────────────────
echo "\nSchritt 2: Attachment-Map aufbauen...\n";
$attachment_map = []; // wp_post_id => attachment_url

foreach ($xml->channel->item as $item) {
    $wp_ns   = $item->children('wp', true);
    $type    = (string)$wp_ns->post_type;
    $post_id = (int)$wp_ns->post_id;
    if ($type === 'attachment') {
        $url = (string)$wp_ns->attachment_url;
        if ($url) $attachment_map[$post_id] = $url;
    }
}
echo "  Attachments gefunden: " . count($attachment_map) . "\n";

// ── Schritt 3: Featured-Image-Map aufbauen (post_id => attachment_post_id) ───
echo "\nSchritt 3: Featured-Image-Map aufbauen...\n";
$thumbnail_map = []; // post_wp_id => attachment_post_id

foreach ($xml->channel->item as $item) {
    $wp_ns   = $item->children('wp', true);
    $type    = (string)$wp_ns->post_type;
    if ($type !== 'post') continue;
    $post_id = (int)$wp_ns->post_id;

    foreach ($wp_ns->postmeta as $meta) {
        $key = (string)$meta->meta_key;
        $val = (string)$meta->meta_value;
        if ($key === '_thumbnail_id' && $val) {
            $thumbnail_map[$post_id] = (int)$val;
            break;
        }
    }
}
echo "  Featured Images zugeordnet: " . count($thumbnail_map) . "\n";

// ── Hilfsfunktion: Bild herunterladen ────────────────────────────────────────
function download_image(string $url, string $img_dir): ?string {
    $filename = basename(parse_url($url, PHP_URL_PATH));
    if (!$filename) return null;
    $local_path = $img_dir . $filename;
    $local_url  = '/assets/img/uploads/' . $filename;

    if (file_exists($local_path)) return $local_url; // schon da

    $ctx = stream_context_create(['http' => [
        'timeout'    => 15,
        'user_agent' => 'Mozilla/5.0 (compatible; TFCImport/1.0)',
    ]]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false) return null;
    file_put_contents($local_path, $data);
    return $local_url;
}

// ── Schritt 4: Artikel reimportieren ─────────────────────────────────────────
echo "\nSchritt 4: Artikel reimportieren...\n";

$count_updated  = 0;
$count_new      = 0;
$count_skip     = 0;
$count_img_ok   = 0;
$count_img_fail = 0;
$total          = 0;

// Default-Kategorie (News)
$default_cat_id = $cat_map['news'] ?? 1;

foreach ($xml->channel->item as $item) {
    $wp_ns  = $item->children('wp', true);
    $type   = (string)$wp_ns->post_type;
    $status = (string)$wp_ns->status;

    if ($type !== 'post') continue;
    if (!in_array($status, ['publish', 'future'])) { $count_skip++; continue; }

    $total++;
    $wp_post_id = (int)$wp_ns->post_id;
    $title      = (string)$item->title;
    $slug       = (string)$wp_ns->post_name ?: sanitize_slug($title);
    $content    = (string)$item->children('content', true)->encoded ?? '';
    $excerpt    = (string)$item->children('excerpt', true)->encoded ?? '';
    $pub_date   = date('Y-m-d H:i:s', strtotime((string)$item->pubDate) ?: time());

    // Kategorie bestimmen
    $cat_id = $default_cat_id;
    foreach ($item->category as $cat) {
        $domain   = (string)$cat['domain'];
        $nicename = (string)$cat['nicename'];
        if ($domain === 'category' && isset($cat_map[$nicename])) {
            $cat_id = $cat_map[$nicename];
            break;
        }
    }

    // Featured Image
    $featured_image = null;
    if (isset($thumbnail_map[$wp_post_id])) {
        $att_id = $thumbnail_map[$wp_post_id];
        if (isset($attachment_map[$att_id])) {
            $local = download_image($attachment_map[$att_id], $img_dir);
            if ($local) { $featured_image = $local; $count_img_ok++; }
            else $count_img_fail++;
        }
    }

    // Falls kein Featured Image: erstes Bild im Content suchen
    if (!$featured_image) {
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $content, $m)) {
            $src = $m[1];
            if (str_contains($src, 'thelastchapter.de')) {
                $local = download_image($src, $img_dir);
                if ($local) { $featured_image = $local; $count_img_ok++; }
            }
        }
    }

    // Prüfen ob Artikel schon existiert (via wp_post_id)
    $stmt = $db->prepare("SELECT id FROM articles WHERE wp_post_id = ?");
    $stmt->execute([$wp_post_id]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        // Update: Kategorie + Bild korrigieren
        $sql = "UPDATE articles SET category_id = ?, created_at = ?";
        $params = [$cat_id, $pub_date];
        if ($featured_image) { $sql .= ", featured_image = ?"; $params[] = $featured_image; }
        $sql .= " WHERE wp_post_id = ?";
        $params[] = $wp_post_id;
        $db->prepare($sql)->execute($params);
        $count_updated++;
    } else {
        // Neu anlegen
        // Slug eindeutig machen
        $base_slug = $slug;
        $suffix = 1;
        while (true) {
            $chk = $db->prepare("SELECT id FROM articles WHERE slug = ?");
            $chk->execute([$slug]);
            if (!$chk->fetchColumn()) break;
            $slug = $base_slug . '-' . $suffix++;
        }
        $db->prepare("INSERT INTO articles
            (title, slug, content, excerpt, category_id, author, featured_image, status, created_at, wp_post_id)
            VALUES (?, ?, ?, ?, ?, 'Redaktion', ?, 'published', ?, ?)")
        ->execute([$title, $slug, $content, $excerpt, $cat_id, $featured_image, $pub_date, $wp_post_id]);
        $count_new++;
    }

    if ($total % 100 === 0) {
        echo "  $total Artikel verarbeitet... (Neu: $count_new | Updated: $count_updated | Bilder OK: $count_img_ok)\n";
        flush();
    }
}

function sanitize_slug(string $title): string {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return substr($slug, 0, 200) ?: 'artikel-' . time();
}

echo "\n============================================================\n";
echo "Import abgeschlossen!\n";
echo "  Verarbeitet : $total\n";
echo "  Neu         : $count_new\n";
echo "  Updated     : $count_updated\n";
echo "  Übersprungen: $count_skip\n";
echo "  Bilder OK   : $count_img_ok\n";
echo "  Bilder Fehler: $count_img_fail\n";
echo "============================================================\n";
