<?php
/**
 * News-Scraper für The Final Chapter
 * Holt News von: Metal Hammer DE, Rock Hard DE, Legacy Metal
 * Läuft als Cronjob oder manuell via CLI: php import/news_scraper.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

$options = getopt('', ['status::']);
$importStatus = $options['status'] ?? getenv('TFC_SCRAPER_STATUS') ?: 'published';
if (!in_array($importStatus, ['draft', 'published'], true)) {
    fwrite(STDERR, "Ungültiger Status: {$importStatus}. Erlaubt: draft, published\n");
    exit(2);
}

// Upload-Verzeichnis
$uploadDir = __DIR__ . '/../assets/img/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Kategorie-ID für "News" holen oder anlegen
function getOrCreateCategory(PDO $db, string $name, string $slug): int {
    $st = $db->prepare("SELECT id FROM categories WHERE slug = ?");
    $st->execute([$slug]);
    $row = $st->fetch();
    if ($row) return (int)$row['id'];
    $db->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)")->execute([$name, $slug]);
    return (int)$db->lastInsertId();
}

function classifyFeedItem(string $title, string $excerpt): array {
    $plain = trim(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if (preg_match('/^\s*Review\s*:/iu', $plain)) {
        return ['cat_name' => 'Reviews', 'cat_slug' => 'reviews', 'force_status' => 'draft'];
    }
    if (preg_match('/^\s*(Bericht|Galerie)\s*:/iu', $plain)) {
        return ['cat_name' => 'Liveberichte/Festivals', 'cat_slug' => 'festivals', 'force_status' => 'draft'];
    }
    return ['cat_name' => 'News', 'cat_slug' => 'news', 'force_status' => null];
}


// Bild herunterladen und lokal speichern
function downloadImage(string $url, string $uploadDir): ?string {
    if (empty($url)) return null;
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $allowed)) $ext = 'jpg';
    $filename = 'news_' . md5($url) . '.' . $ext;
    $localPath = $uploadDir . $filename;
    if (file_exists($localPath)) return '/assets/img/uploads/' . $filename;
    $ctx = stream_context_create(['http' => [
        'timeout' => 10,
        'user_agent' => 'Mozilla/5.0 (compatible; TFCScraper/1.0)',
        'follow_location' => true,
    ]]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data && strlen($data) > 1000) {
        file_put_contents($localPath, $data);
        return '/assets/img/uploads/' . $filename;
    }
    return null;
}

// Slug erzeugen
function makeSlug(string $title): string {
    $title = mb_strtolower($title);
    $title = str_replace(['ä','ö','ü','ß'], ['ae','oe','ue','ss'], $title);
    $title = preg_replace('/[^a-z0-9]+/', '-', $title);
    return trim($title, '-');
}

// TFC-Hausstil: aus Feed-Meldungen kurze Magazin-News bauen.
function cleanFeedText(string $text): string {
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/<\/?(p|div|br|li|ul|ol|h[1-6])[^>]*>/i', ' ', $text);
    $text = strip_tags($text);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = preg_replace('/(?<=[.!?])(?=\p{Lu})/u', ' ', $text);
    $text = preg_replace('/\s*Read More\/Discuss.*$/i', '', $text);
    return trim($text);
}

function normalizeFeedTitle(string $title): string {
    $title = cleanFeedText($title);
    $title = preg_replace('/^(?:news|meldung|pressemeldung)\s*[.:\-–—]+\s*/iu', '', $title);
    $title = preg_replace('/\s+/', ' ', $title);
    $title = trim($title, " \t\n\r\0\x0B-–—:.;");

    $replacements = [
        '/\bPremiere(?:s)?\s+New\s+Single\s*&\s*Music\s+Video\b/iu' => 'veröffentlichen neue Single samt Video',
        '/\bPremiere(?:s)?\s+New\s+Single\s+and\s+Music\s+Video\b/iu' => 'veröffentlichen neue Single samt Video',
        '/\bPremiere(?:s)?\s+New\s+Single\s*&\s*Lyric\s+Video\s+For\b/iu' => 'veröffentlichen neue Single samt Lyric-Video zu',
        '/\bPremiere(?:s)?\s+New\s+Lyric\s+Video\s+For\b/iu' => 'veröffentlichen neues Lyric-Video zu',
        '/\bPremiere(?:s)?\s+New\s+Single\s*&\s*Visualizer\b/iu' => 'veröffentlichen neue Single samt Visualizer',
        '/\bPremiere(?:s)?\s+New\s+Single\b/iu' => 'veröffentlichen neue Single',
        '/\brelease\s+second\s+Single\s*\/\s*Video\b/iu' => 'veröffentlichen zweite Single samt Video',
        '/\bmusic\s+video\s+out\s*;\s*on\s+Tour\s+with\b/iu' => 'veröffentlichen neues Video und gehen auf Tour mit',
        '/\bmusic\s+video\s+out\b/iu' => 'veröffentlichen neues Video',
        '/\bon\s+Tour\s+with\b/iu' => 'auf Tour mit',
        '/\bdrop\s+new\s+advance\s+single\b/iu' => 'veröffentlichen neue Vorab-Single',
        '/\bdrop\s+new\s+single\b/iu' => 'veröffentlichen neue Single',
        '/\breleased\s+a\s+new\s+single\b/iu' => 'veröffentlichen neue Single',
        '/\bhas\s+released\s+a\s+new\s+single\b/iu' => 'veröffentlicht neue Single',
        '/\bhave\s+announced\b/iu' => 'kündigen an',
        '/\bhas\s+announced\b/iu' => 'kündigt an',
        '/\bhave\s+unveiled\b/iu' => 'stellen vor',
        '/\bhas\s+unveiled\b/iu' => 'stellt vor',
        '/\bReimagine\s+[’\']?80s\s+Classic\b/iu' => 'interpretieren Achtziger-Klassiker neu',
        '/\bannounces\s+new\s+album\b/iu' => 'kündigt neues Album an',
        '/\bunveils\s+first\s+single\s*\/\s*video\b/iu' => 'stellt erste Single samt Video vor',
        '/\bunveils\s+new\s+single\b/iu' => 'stellt neue Single vor',
        '/\bfrom\s+upcoming\s+new\s+self-titled\s+album\b/iu' => 'vom kommenden selbstbetitelten Album',
        '/\bfrom\s+upcoming\s+new\s+album\b/iu' => 'vom kommenden neuen Album',
        '/\bout\s+Oct\.?\s*/iu' => 'erscheint im Oktober ',
        '/\bout\s+September\b/iu' => 'erscheint im September',
    ];
    foreach ($replacements as $pattern => $replacement) {
        $title = preg_replace($pattern, $replacement, $title);
    }

    return trim(preg_replace('/\s+/', ' ', $title));
}


function cleanArticleSnippet(string $text): string {
    $text = cleanFeedText($text);
    $replacements = [
        '/^International\s+([A-Z][A-Za-z\s\-]+?)\s+band\s+/u' => '$1 ',
        '/\brelease\s+second\s+Single\s*\/\s*Video\b/iu' => 'veröffentlichen zweite Single samt Video',
        '/\bmusic\s+video\s+out\s*;\s*on\s+Tour\s+with\b/iu' => 'veröffentlichen neues Video und gehen auf Tour mit',
        '/\bmusic\s+video\s+out\b/iu' => 'veröffentlichen neues Video',
        '/\bon\s+Tour\s+with\b/iu' => 'auf Tour mit',
        '/\bdrop\s+new\s+advance\s+single\b/iu' => 'veröffentlichen neue Vorab-Single',
        '/\bdrop\s+new\s+single\b/iu' => 'veröffentlichen neue Single',
        '/\bhave\s+released\b/iu' => 'veröffentlichen',
        '/\breleased\s+a\s+new\s+single\b/iu' => 'veröffentlichen eine neue Single',
        '/\bhas\s+released\s+a\s+new\s+single\b/iu' => 'veröffentlicht eine neue Single',
        '/\bthe\s+release\s+was\s+recorded\s+at\b/iu' => 'aufgenommen wurde die Veröffentlichung in',
        '/\bwill\s+release\s+their\s+new\s+album\b/iu' => 'veröffentlichen ihr neues Album',
        '/\bwill\s+release\s+his\s+new\s+album\b/iu' => 'veröffentlicht sein neues Album',
        '/\bannounce(?:s|d)?\s+new\s+album\b/iu' => 'kündigen ein neues Album an',
        '/\bannounce(?:s|d)?\s+new\s+single\b/iu' => 'kündigen eine neue Single an',
        '/\bunveil(?:s|ed)?\s+new\s+single\b/iu' => 'stellen eine neue Single vor',
        '/\bpremiere(?:s|d)?\s+new\s+single\b/iu' => 'präsentieren eine neue Single',
        '/\bmusic\s+video\b/iu' => 'Musikvideo',
        '/\blyric\s+video\b/iu' => 'Lyric-Video',
        '/\bupcoming\s+album\b/iu' => 'kommendes Album',
        '/\bItalian\s+symphonic\s+power\s+metallers\s+/iu' => 'Die italienischen Symphonic-Power-Metaller ',
        '/\bdelve\s+deeper\s+into\s+their\s+personal\s+deck\s+of\s+Tarot\s+with\s+the\s+release\s+of\b/iu' => 'legen mit der Veröffentlichung von',
        '/\bwith\s+the\s+release\s+of\b/iu' => 'mit der Veröffentlichung von',
        '/\bis\s+an\s+uncompromising\s+examination\s+of\s+humanity\s+at\s+its\s+most\s+brutal\b/iu' => 'ist ein kompromissloser Blick auf die brutalsten Seiten der Menschheit',
        '/\bThe\s+new\s+full-length\s+is\s+scheduled\s+t(?:o|…)\b/iu' => 'Das neue Album ist angekündigt',
        '/\bis\s+scheduled\s+t(?:o|…)\b/iu' => 'ist angekündigt',
        '/\bAustralian\s+deathcore\s+band\s+/iu' => 'Die australische Deathcore-Band ',
        '/\bnoted\s+animal\s+rights\s+activists\b/iu' => 'bekannten Tierrechtsaktivisten',
        '/\bhave\s+released\s+their\s+new\s+single\b/iu' => 'veröffentlichen ihre neue Single',
        '/\btheir\s+take\s+on\b/iu' => 'ihre Version von',
        '/\brecorded\s+during\s+a\s+recent?\b/iu' => 'aufgenommen bei einer aktuellen',
        '/\brecorded\s+during\b/iu' => 'aufgenommen bei',
        '/\bchoose\s+continuous\s+escalation\s+with\s+their\s+next\s+advance\s+track\b/iu' => 'setzen mit ihrem nächsten Vorab-Track weiter auf Eskalation',
        '/\bAnother\s+new\s+track\s+from\b/iu' => 'Ein weiterer neuer Track von',
        '/\blong-awaited\s+kommendes\s+Album\b/iu' => 'lange erwarteten kommenden Album',
        '/\blong-awaited\s+upcoming\s+album\b/iu' => 'lange erwarteten kommenden Album',
        '/\bhas\s+arrived\b/iu' => 'ist da',
        '/\bveterans\s+have\s+premiered\b/iu' => 'Veteranen präsentieren',
        '/\bhave\s+premiered\b/iu' => 'präsentieren',
        '/\bhas\s+premiered\b/iu' => 'präsentiert',
        '/\bhave\s+announced\b/iu' => 'kündigen an',
        '/\bhas\s+announced\b/iu' => 'kündigt an',
        '/\bhave\s+unveiled\b/iu' => 'stellen vor',
        '/\bhas\s+unveiled\b/iu' => 'stellt vor',
        '/\bGerman\s+electronicore\s+band\s+/iu' => 'Die deutsche Electronicore-Band ',
        '/\btheir\s+personal\s+deck\s+of\s+Tarot\b/iu' => 'ihr persönliches Tarot-Deck',
        '/\bEast\s+Coast\s+thrash\s+metal\s+veterans\b/iu' => 'East-Coast-Thrash-Veteranen',
    ];
    foreach ($replacements as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, (string)$text);
    }
    return trim(preg_replace('/\s+/', ' ', (string)$text));
}

function sourceLabel(string $sourceName): string {
    return match ($sourceName) {
        'Metal Hammer DE' => 'Metal Hammer',
        default => $sourceName,
    };
}

function makeTfcTitle(string $title, string $excerpt): string {
    $plain = normalizeFeedTitle($title);
    $lower = mb_strtolower($plain . ' ' . $excerpt);

    if (mb_strlen($plain) > 78 || str_contains($plain, ':')) {
        return rtrim(mb_substr($plain, 0, 95), " -–—,:;") . (mb_strlen($plain) > 95 ? '…' : '');
    }

    if (str_contains($lower, 'new album') || str_contains($lower, 'neues album') || str_contains($lower, 'studio album')) {
        return $plain . ': neues Futter für die Anlage';
    }
    if (str_contains($lower, 'single') || str_contains($lower, 'music video') || str_contains($lower, 'track')) {
        return $plain . ': neuer Song, klare Ansage';
    }
    if (str_contains($lower, 'tour') || str_contains($lower, 'shows') || str_contains($lower, 'festival')) {
        return $plain . ': Termine, Druck und Vorfreude';
    }
    if (str_contains($lower, 'cover') || str_contains($lower, 'tribute')) {
        return $plain . ': Tribut mit Gewicht';
    }
    if (mb_strlen($plain) > 95) {
        $plain = rtrim(mb_substr($plain, 0, 92), " -–—,:;") . '…';
    }
    return $plain;
}

function buildTfcNewsText(string $title, string $excerpt, string $link, string $sourceName): array {
    $title = normalizeFeedTitle($title);
    $excerpt = cleanFeedText($excerpt);
    $styledTitle = makeTfcTitle($title, $excerpt);
    $source = sourceLabel($sourceName);

    $firstSentence = $excerpt;
    $bodyText = '';
    if (preg_match('/^(.{80,260}?[.!?])\s*(.*)$/us', $excerpt, $m)) {
        $firstSentence = trim($m[1]);
        $bodyText = trim($m[2]);
    } elseif (mb_strlen($firstSentence) > 260) {
        $firstSentence = rtrim(mb_substr($firstSentence, 0, 255), " ,;:-–—") . '.';
        $bodyText = trim(mb_substr($excerpt, mb_strlen($firstSentence)));
    }
    if ($bodyText === '') {
        $bodyText = $excerpt;
    }

    $lower = mb_strtolower($title . ' ' . $excerpt);
    if (str_contains($lower, 'album')) {
        $hook = 'Die Verstärker sind noch warm, da steht schon der nächste Brocken im Raum.';
        $angle = 'Für Fans heißt das: Kalender zücken, Anlage freiräumen und schon mal prüfen, ob die Nachbarn wirklich so tolerant sind, wie sie behaupten.';
        $punch = 'Kurz gesagt: Das hier riecht nach neuer Munition für die Playlist.';
    } elseif (str_contains($lower, 'single') || str_contains($lower, 'video') || str_contains($lower, 'track')) {
        $hook = 'Ein neuer Song ist immer ein kurzer Wahrheitsmoment: viel Gerede passt nicht rein, der erste Schlag muss sitzen.';
        $angle = 'Ob daraus ein großer Wurf wird, entscheidet später das Gesamtpaket — als erstes Lebenszeichen macht die Nummer aber genau das, was sie soll: Aufmerksamkeit ziehen.';
        $punch = 'Kurz gesagt: Kein Roman, sondern ein Riff mit Visitenkarte.';
    } elseif (str_contains($lower, 'tour') || str_contains($lower, 'shows') || str_contains($lower, 'festival')) {
        $hook = 'Live-Termine sind die ehrliche Währung dieser Szene: Am Ende zählt, was vor der Bühne passiert.';
        $angle = 'Wer dabei sein will, sollte nicht zu lange romantisch auf den Warenkorb starren. Gute Abende sind meistens schneller weg als der letzte Becherpfand.';
        $punch = 'Kurz gesagt: Der Kalender bekommt wieder Kratzer.';
    } elseif (str_contains($lower, 'ozzy') || str_contains($lower, 'cornell') || str_contains($lower, 'tribute')) {
        $hook = 'Manche Namen stehen nicht einfach in einer Meldung — sie werfen Schatten bis in die Gegenwart.';
        $angle = 'Wichtig ist dabei der Ton: Würdigung ja, billige Nostalgie nein. Legenden brauchen keinen Zuckerguss, sie haben Gewicht genug.';
        $punch = 'Kurz gesagt: Erinnerung mit Strom, nicht mit Staubschicht.';
    } else {
        $hook = 'Die Szene schläft nicht — sie dreht nur kurz den Amp leiser, bevor der nächste Einschlag kommt.';
        $angle = 'Spannend wird jetzt, ob aus der Meldung mehr wird als nur ein kurzer Ausschlag im News-Ticker. Das Potenzial für Gesprächsstoff ist jedenfalls da.';
        $punch = 'Kurz gesagt: Kein Grund zur Panik, aber einer zum Hinhören.';
    }

    $content = '<p><strong>' . htmlspecialchars($hook, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong> ' . htmlspecialchars($firstSentence, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>' . "\n"
        . '<p>' . htmlspecialchars($bodyText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>' . "\n"
        . '<p>' . htmlspecialchars($angle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>' . "\n"
        . '<p><strong>' . htmlspecialchars($punch, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong></p>' . "\n"
        . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" target="_blank" rel="noopener">→ Quelle / mehr dazu bei ' . htmlspecialchars($source, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a></p>';

    $short = cleanArticleSnippet($excerpt);
    if (mb_strlen($short) > 220) $short = rtrim(mb_substr($short, 0, 220), " ,;:-–—") . '…';
    return [
        'title' => $styledTitle,
        'excerpt' => $short,
        'content' => $content,
    ];
}

// Artikel in DB speichern
function saveArticle(PDO $db, array $data, string $status = 'published'): bool {
    $slug = makeSlug($data['original_title'] ?? $data['title']);
    // Duplikat-Check über Slug oder externe URL
    $st = $db->prepare("SELECT id FROM articles WHERE slug = ? OR (wp_post_id IS NULL AND title = ?)");
    $st->execute([$slug, $data['title']]);
    if ($st->fetch()) return false; // bereits vorhanden

    // Slug eindeutig machen
    $baseSlug = $slug;
    $i = 1;
    while (true) {
        $c = $db->prepare("SELECT id FROM articles WHERE slug = ?");
        $c->execute([$slug]);
        if (!$c->fetch()) break;
        $slug = $baseSlug . '-' . $i++;
    }

    $db->prepare("
        INSERT INTO articles (title, slug, content, excerpt, category_id, author, featured_image, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ")->execute([
        $data['title'],
        $slug,
        $data['content'],
        $data['excerpt'],
        $data['category_id'],
        $data['author'],
        $data['featured_image'],
        $status,
        $data['published_at'],
    ]);
    return true;
}

// ============================================================
// RSS-Feed parsen
// ============================================================
function parseFeed(string $url): array {
    $ctx = stream_context_create(['http' => [
        'timeout' => 15,
        'user_agent' => 'Mozilla/5.0 (compatible; TFCScraper/1.0)',
    ]]);
    $xml = @file_get_contents($url, false, $ctx);
    if (!$xml) return [];
    libxml_use_internal_errors(true);
    $feed = simplexml_load_string($xml);
    if (!$feed) return [];
    $items = [];
    foreach ($feed->channel->item as $item) {
        $ns = $item->getNamespaces(true);
        // Bild aus media:content oder enclosure oder content:encoded
        $image = '';
        if (isset($ns['media'])) {
            $media = $item->children($ns['media']);
            if (isset($media->content)) {
                $attrs = $media->content->attributes();
                $image = (string)($attrs['url'] ?? '');
            }
            if (!$image && isset($media->thumbnail)) {
                $attrs = $media->thumbnail->attributes();
                $image = (string)($attrs['url'] ?? '');
            }
        }
        if (!$image) {
            $enc = $item->enclosure;
            if ($enc) {
                $attrs = $enc->attributes();
                $type = (string)($attrs['type'] ?? '');
                if (str_starts_with($type, 'image/')) {
                    $image = (string)($attrs['url'] ?? '');
                }
            }
        }
        // Bild aus content:encoded extrahieren
        if (!$image && isset($ns['content'])) {
            $content = $item->children($ns['content']);
            $html = (string)($content->encoded ?? '');
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $html, $m)) {
                $image = $m[1];
            }
        }
        // Datum
        $pubDate = (string)$item->pubDate;
        $ts = $pubDate ? strtotime($pubDate) : time();
        $items[] = [
            'title'   => trim((string)$item->title),
            'link'    => trim((string)$item->link),
            'excerpt' => trim(strip_tags((string)$item->description)),
            'image'   => $image,
            'date'    => date('Y-m-d H:i:s', $ts ?: time()),
        ];
    }
    return $items;
}

// ============================================================
// Quellen definieren
// ============================================================
$sources = [
    [
        'name'     => 'Metal Hammer DE',
        'feed'     => 'https://www.metal-hammer.de/feed/',
        'cat_name' => 'News',
        'cat_slug' => 'news',
        'author'   => 'Metal Hammer',
    ],
    [
        'name'     => 'Metalglory',
        'feed'     => 'https://www.metalglory.de/feed/',
        'cat_name' => 'News',
        'cat_slug' => 'news',
        'author'   => 'Metalglory',
    ],
    [
        'name'     => 'Deaf Forever',
        'feed'     => 'https://www.deaf-forever.de/feed/',
        'cat_name' => 'News',
        'cat_slug' => 'news',
        'author'   => 'Deaf Forever',
    ],
    [
        'name'     => 'Metal Underground',
        'feed'     => 'https://feeds.feedburner.com/metalunderground',
        'cat_name' => 'News',
        'cat_slug' => 'news',
        'author'   => 'Metal Underground',
    ],
];

// ============================================================
// Hauptschleife
// ============================================================
$total = 0;
$neu   = 0;
$errors = [];

echo "Importstatus: {$importStatus}\n";

foreach ($sources as $src) {
    echo "Lade {$src['name']} ... ";
    $catId = getOrCreateCategory($db, $src['cat_name'], $src['cat_slug']);
    $items = parseFeed($src['feed']);
    if (empty($items)) {
        echo "FEHLER: Feed leer oder nicht erreichbar\n";
        $errors[] = $src['name'];
        continue;
    }
    echo count($items) . " Einträge\n";
    $total += count($items);
    foreach ($items as $item) {
        if (empty($item['title'])) continue;
        $classification = classifyFeedItem($item['title'], $item['excerpt']);
        $catId = getOrCreateCategory($db, $classification['cat_name'], $classification['cat_slug']);
        // Bild herunterladen
        $localImg = null;
        if ($item['image']) {
            $localImg = downloadImage($item['image'], $uploadDir);
        }
        $normalizedTitle = normalizeFeedTitle($item['title']);
        $styled = buildTfcNewsText($normalizedTitle, $item['excerpt'], $item['link'], $src['name']);
        $saved = saveArticle($db, [
            'original_title' => $normalizedTitle,
            'title'        => $styled['title'],
            'content'      => $styled['content'],
            'excerpt'      => $styled['excerpt'],
            'category_id'  => $catId,
            'author'       => 'Redaktion',
            'featured_image' => $localImg ?? $item['image'],
            'published_at' => $item['date'],
        ], $classification['force_status'] ?? $importStatus);
        if ($saved) $neu++;
    }
}

echo "------------------------------------------------------------\n";
echo "Gesamt: $total | Neu: $neu | Übersprungen: " . ($total - $neu) . "\n";
if ($errors) echo "Fehlerhafte Quellen: " . implode(', ', $errors) . "\n";
echo "Fertig.\n";
