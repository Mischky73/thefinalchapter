<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

const FESTIVAL_NEWS_PARENT_ID = 54;
const STATE_FILE = __DIR__ . '/festival_news_state.json';
const LOG_FILE = __DIR__ . '/festival_news.log';
const IMAGE_DIR = __DIR__ . '/../assets/img/uploads/festival-news';

$checkedAt = (new DateTimeImmutable('now', new DateTimeZone('Europe/Berlin')))->format(DATE_ATOM);
$sources = [
    'wacken-open-air' => ['name' => 'Wacken Open Air', 'canonical_domain' => 'www.wacken.com', 'archive_url' => 'https://www.wacken.com/de/alle-news/'],
    'rockharz' => ['name' => 'ROCKHARZ', 'canonical_domain' => 'www.rockharz-festival.com', 'archive_url' => 'https://www.rockharz-festival.com/news'],
    'summer-breeze' => ['name' => 'Summer Breeze', 'canonical_domain' => 'www.summer-breeze.de', 'archive_url' => 'https://www.summer-breeze.de/de/news/', 'feed_url' => 'https://www.summer-breeze.de/de/feed/'],
    'party-san' => ['name' => 'Party.San', 'canonical_domain' => 'www.party-san.de', 'archive_url' => 'https://www.party-san.de/news/'],
    'in-flammen' => ['name' => 'In Flammen', 'canonical_domain' => 'www.in-flammen.com', 'archive_url' => 'https://www.in-flammen.com/'],
    'full-rewind' => ['name' => 'Full Rewind', 'canonical_domain' => 'full-rewind.de', 'archive_url' => 'https://full-rewind.de/'],
];

$records = [
    [
        'title' => 'W:O:A 2026: Limitierte ICECUBE-X-40-Kühlbox vorgestellt',
        'slug' => 'woa-2026-limitierte-icecube-x-40-kuehlbox',
        'date' => '2026-07-15 12:00:00',
        'category_slug' => 'wacken-open-air',
        'excerpt' => 'MAENTUM und das Wacken Open Air stellen eine auf 300 Stück begrenzte W:O:A-Ausgabe der Kompressor-Kühlbox ICECUBE X 40 vor.',
        'source_url' => 'https://www.wacken.com/de/news-details/icecube-x-woa-limited-edition-eiskalt-durch-wacken/',
        'image_url' => 'https://www.wacken.com/fileadmin/_processed_/8/4/csm_MAENTUM_ICECUBEX40_WOA_WebsiteNews_Header_1920x1080_9255b7946d.jpg',
        'image_name' => '2026-07-15-wacken-icecube-x-40-limited-edition.jpg',
        'content' => '<p>Das Wacken Open Air hat gemeinsam mit <strong>MAENTUM</strong> eine limitierte W:O:A-Ausgabe der Kompressor-Kühlbox <strong>ICECUBE X 40</strong> vorgestellt. Die Sonderedition ist nach Angaben der offiziellen Meldung auf 300 Stück begrenzt.</p><p>Die Kühlbox kann bis minus 20 Grad Celsius kühlen und lässt sich per App oder Touchdisplay steuern. Sie unterstützt 12- und 24-Volt-Anschlüsse sowie 230 Volt. Laut Hersteller soll die Vakuum-Isolierung den Energieverbrauch um bis zu 40 Prozent senken.</p><p><strong>Offizielle Quelle:</strong> <a href="https://www.wacken.com/de/news-details/icecube-x-woa-limited-edition-eiskalt-durch-wacken/" target="_blank" rel="noopener noreferrer">Meldung des Wacken Open Air vom 15.07.2026</a></p>',
    ],
    [
        'title' => 'SUMMER BREEZE 2026 veröffentlicht Festival- und Infield-Pläne',
        'slug' => 'summer-breeze-2026-gelaendeplaene-online',
        'date' => '2026-07-15 12:00:00',
        'category_slug' => 'sb',
        'excerpt' => 'Die Festival Map und die Infield Map für das SUMMER BREEZE 2026 stehen im offiziellen Downloadbereich als PDF bereit.',
        'source_url' => 'https://www.summer-breeze.de/de/news/gelaendeplaene-jetzt-online-229155/',
        'image_url' => 'https://www.summer-breeze.de/wp-content/uploads/2026/07/15/FestivalMapsAvailable_1215x735.jpg',
        'image_name' => '2026-07-15-summer-breeze-gelaendeplaene.jpg',
        'content' => '<p>Das <strong>SUMMER BREEZE Open Air</strong> hat die Geländepläne für 2026 veröffentlicht. Verfügbar sind eine Festival Map und eine gesonderte Infield Map.</p><p>Beide Pläne stehen im Downloadbereich der offiziellen Festivalwebseite als PDF bereit. Nach Angaben des Veranstalters sollen sie außerdem in der Festival-App verfügbar gemacht werden.</p><p><strong>Offizielle Quelle:</strong> <a href="https://www.summer-breeze.de/de/news/gelaendeplaene-jetzt-online-229155/" target="_blank" rel="noopener noreferrer">Meldung des SUMMER BREEZE Open Air vom 15.07.2026</a></p>',
    ],
    [
        'title' => 'W:O:A 2026: Harry Metal zeigt Solaranlage, Bierpipeline und Bühnenaufbau',
        'slug' => 'woa-2026-harry-metal-episode-4-solaranlage-bierpipeline',
        'date' => '2026-07-16 12:00:00',
        'category_slug' => 'wacken-open-air',
        'excerpt' => 'Die vierte Harry-Metal-Folge 2026 dokumentiert weitere Aufbauarbeiten auf dem W:O:A-Gelände.',
        'source_url' => 'https://www.wacken.com/de/news-details/woa-2026-harry-metal-episode-4/',
        'image_url' => 'https://www.wacken.com/fileadmin/_processed_/a/b/csm_HarryMetal_WOA2026_04_4d288b7c86.jpg',
        'image_name' => '2026-07-16-wacken-harry-metal-episode-4.jpg',
        'content' => '<p>Das <strong>Wacken Open Air</strong> hat die vierte Ausgabe der Harry-Metal-Reihe zum Aufbau des W:O:A 2026 veröffentlicht.</p><p>Nach Angaben der offiziellen Meldung werden darin unter anderem die Bierpipeline, eine große Solaranlage, die Ankunft der ersten Bühnenbauer und zusätzliche Schattenflächen gezeigt. Eine weitere Folge wurde für den folgenden Tag angekündigt.</p><p><strong>Offizielle Quelle:</strong> <a href="https://www.wacken.com/de/news-details/woa-2026-harry-metal-episode-4/" target="_blank" rel="noopener noreferrer">Meldung des Wacken Open Air vom 16.07.2026</a></p>',
    ],
    [
        'title' => 'W:O:A 2026 veröffentlicht Shuttlebus-Fahrpläne',
        'slug' => 'woa-2026-shuttlebus-fahrplaene-veroeffentlicht',
        'date' => '2026-07-16 12:00:00',
        'category_slug' => 'wacken-open-air',
        'excerpt' => 'Das Wacken Open Air hat die Fahrpläne für die Shuttlebusse zwischen Itzehoe und den Busplätzen in Wacken veröffentlicht.',
        'source_url' => 'https://www.wacken.com/de/news-details/die-shuttlebus-plaene-fuer-das-woa-2026-sind-da/',
        'image_url' => 'https://www.wacken.com/fileadmin/_processed_/9/6/csm_WOA26_Shuttlebusplan_260714_Web-Banner-DE_716182c71b.jpg',
        'image_name' => '2026-07-16-wacken-shuttlebus-fahrplaene.jpg',
        'content' => '<p>Das <strong>Wacken Open Air</strong> hat die Shuttlebus-Fahrpläne für 2026 veröffentlicht. Die Busse sollen vom 26. Juli bis zum 2. August 2026 mehrmals pro Stunde zwischen Itzehoe und den W:O:A-Busplätzen Ost und West verkehren.</p><p>Für Festival-Ticketinhaber ist dieser Shuttle während der Festivalwoche kostenfrei. Zusätzlich ist von Montag bis Samstag zwischen 10 und 18 Uhr ein Pool-Shuttle durch Wacken vorgesehen. Dieser soll alle 30 Minuten fahren; laut offizieller Meldung kostet eine Fahrt zwei Euro und ist bar zu bezahlen.</p><p><strong>Offizielle Quelle:</strong> <a href="https://www.wacken.com/de/news-details/die-shuttlebus-plaene-fuer-das-woa-2026-sind-da/" target="_blank" rel="noopener noreferrer">Meldung des Wacken Open Air vom 16.07.2026</a></p>',
    ],
    [
        'title' => 'W:O:A 2026: Harry Metal berichtet über weitere Aufbauarbeiten',
        'slug' => 'woa-2026-harry-metal-episode-3-aufbauarbeiten',
        'date' => '2026-07-16 12:00:00',
        'category_slug' => 'wacken-open-air',
        'excerpt' => 'Die dritte Harry-Metal-Folge 2026 zeigt weitere Infrastrukturarbeiten auf dem Festivalgelände.',
        'source_url' => 'https://www.wacken.com/de/news-details/woa-2026-harry-metal-episode-3/',
        'image_url' => 'https://www.wacken.com/fileadmin/_processed_/2/a/csm_HarryMetal_WOA2026_03__0e5c8e3b46.jpg',
        'image_name' => '2026-07-16-wacken-harry-metal-episode-3.jpg',
        'content' => '<p>Das <strong>Wacken Open Air</strong> hat die dritte Folge der Harry-Metal-Reihe zum Aufbau des Festivals 2026 veröffentlicht.</p><p>Die offizielle Meldung nennt Solarpaneele, Mobilfunkantennen, Schlaf-Pods und das Crew-Catering als aktuelle Bestandteile der Aufbauarbeiten auf dem Festivalgelände.</p><p><strong>Offizielle Quelle:</strong> <a href="https://www.wacken.com/de/news-details/woa-2026-harry-metal-episode-3/" target="_blank" rel="noopener noreferrer">Meldung des Wacken Open Air vom 16.07.2026</a></p>',
    ],
    [
        'title' => 'W:O:A 2026: Nur noch 1000 Tickets im Verkauf',
        'slug' => 'woa-2026-nur-noch-1000-tickets-im-verkauf',
        'date' => '2026-07-17 12:00:00',
        'category_slug' => 'wacken-open-air',
        'excerpt' => 'Das Wacken Open Air meldet, dass die letzten 1000 Tickets für die Jubiläumsausgabe 2026 in den Verkauf gegangen sind.',
        'source_url' => 'https://www.wacken.com/de/news-details/die-letzten-1000-tickets-fuer-das-woa-2026-sind-im-verkauf/',
        'image_url' => 'https://www.wacken.com/fileadmin/_processed_/e/d/csm_WOA26_1000-Tickets_260717_DE_Web-Banner_20489a1cb0.jpg',
        'image_name' => '2026-07-17-wacken-letzte-1000-tickets.jpg',
        'content' => '<p>Das <strong>Wacken Open Air</strong> hat am 17. Juli 2026 den Verkauf der letzten 1000 Tickets für das W:O:A 2026 gemeldet.</p><p>Die offizielle Meldung verweist für Kurzentschlossene auf den regulären Ticketverkauf. Weitere Angaben zum Kontingent macht die Mitteilung nicht.</p><p><strong>Offizielle Quelle:</strong> <a href="https://www.wacken.com/de/news-details/die-letzten-1000-tickets-fuer-das-woa-2026-sind-im-verkauf/" target="_blank" rel="noopener noreferrer">Meldung des Wacken Open Air vom 17.07.2026</a></p>',
    ],
    [
        'title' => 'W:O:A 2026 veröffentlicht Programm für Wasteland und Wackinger Village',
        'slug' => 'woa-2026-programm-wasteland-wackinger-village',
        'date' => '2026-07-17 12:00:00',
        'category_slug' => 'wacken-open-air',
        'excerpt' => 'Das Programm für Wasteland, Wackinger Village und den Feuerwehrtruck beim W:O:A 2026 ist veröffentlicht.',
        'source_url' => 'https://www.wacken.com/de/news-details/das-programm-von-wasteland-wackinger-village-beim-woa-2026/',
        'image_url' => 'https://www.wacken.com/fileadmin/user_upload/news_images/WOA26_Rahmenprogramm-VOE_260717_Web-Banner-DE-sm.jpg',
        'image_name' => '2026-07-17-wacken-wasteland-wackinger-village.jpg',
        'content' => '<p>Das <strong>Wacken Open Air</strong> hat das Rahmenprogramm für Wasteland, Wackinger Village und den Feuerwehrtruck 2026 veröffentlicht.</p><p>Für das Wasteland nennt die Meldung unter anderem Motorshows, Cardrive, Feuershows, Cage Fight und mehrere Musikauftritte. Im Wackinger Village sind Schwertschaukampf, Bruchenball, Tanzworkshop, Walkacts, Feuervarieté und handgemachte Musik vorgesehen. Am Feuerwehrtruck kündigt das Festival verschiedene DJ- und Live-Programmpunkte an.</p><p>Die offizielle Mitteilung führt die einzelnen Acts und Programmbestandteile vollständig auf.</p><p><strong>Offizielle Quelle:</strong> <a href="https://www.wacken.com/de/news-details/das-programm-von-wasteland-wackinger-village-beim-woa-2026/" target="_blank" rel="noopener noreferrer">Meldung des Wacken Open Air vom 17.07.2026</a></p>',
    ],
    [
        'title' => 'W:O:A 2026: Harry Metal besucht besondere Camping-Unterkünfte',
        'slug' => 'woa-2026-harry-metal-episode-5-camping-unterkuenfte',
        'date' => '2026-07-17 12:00:00',
        'category_slug' => 'wacken-open-air',
        'excerpt' => 'Episode 5 der Harry-Metal-Reihe zeigt besondere Camping-Unterkünfte und den Aufbau der Faster- und Harder-Stages.',
        'source_url' => 'https://www.wacken.com/de/news-details/woa-2026-harry-metal-episode-5/',
        'image_url' => 'https://www.wacken.com/fileadmin/_processed_/f/b/csm_HarryMetal_WOA2026_05_c37ca4717e.jpg',
        'image_name' => '2026-07-17-wacken-harry-metal-episode-5.jpg',
        'content' => '<p>Das <strong>Wacken Open Air</strong> hat die fünfte Folge der Harry-Metal-Reihe zum Aufbau 2026 veröffentlicht.</p><p>Die Episode stellt mit <em>Residenz Evil</em> und <em>Moshtel</em> zwei besondere Camping-Unterkünfte vor. Nach Angaben der offiziellen Meldung schreitet außerdem der Aufbau der Faster- und Harder-Stages voran.</p><p><strong>Offizielle Quelle:</strong> <a href="https://www.wacken.com/de/news-details/woa-2026-harry-metal-episode-5/" target="_blank" rel="noopener noreferrer">Meldung des Wacken Open Air vom 17.07.2026</a></p>',
    ],
    [
        'title' => 'W:O:A 2026 veröffentlicht Farmers-Market-Preisliste',
        'slug' => 'woa-2026-farmers-market-preisliste-veroeffentlicht',
        'date' => '2026-07-17 12:00:00',
        'category_slug' => 'wacken-open-air',
        'excerpt' => 'Die vollständige Preisliste des Farmers Market beim W:O:A 2026 steht in zwei Sortierungen bereit.',
        'source_url' => 'https://www.wacken.com/de/news-details/farmers-market-2026-preisliste/',
        'image_url' => 'https://www.wacken.com/fileadmin/_processed_/d/8/csm_WOA26_Farmers-Market-Preisliste_260710_DE_Web-Banner_1bdc16f05e.jpg',
        'image_name' => '2026-07-17-wacken-farmers-market-preisliste.jpg',
        'content' => '<p>Das <strong>Wacken Open Air</strong> hat die vollständige Preisliste für den Farmers Market 2026 veröffentlicht.</p><p>Nach Angaben des Festivals umfasst das Angebot mehrere hundert Produkte. Die Liste steht in zwei Varianten bereit: nach Sortiment sowie nach Partner sortiert.</p><p>Zusätzlich ist erneut eine Getränke-Vorbestellung über das Cashless-Eventportal vorgesehen. Bestellte Getränke können beim Farmers Market abgeholt werden.</p><p><strong>Offizielle Quelle:</strong> <a href="https://www.wacken.com/de/news-details/farmers-market-2026-preisliste/" target="_blank" rel="noopener noreferrer">Meldung des Wacken Open Air vom 17.07.2026</a></p>',
    ],
];

function logLine(string $message): void
{
    file_put_contents(LOG_FILE, '[' . date('c') . '] ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function categoryId(PDO $db, string $slug): int
{
    $stmt = $db->prepare('SELECT id FROM categories WHERE slug = ? AND parent_id = ? LIMIT 1');
    $stmt->execute([$slug, FESTIVAL_NEWS_PARENT_ID]);
    $id = (int)$stmt->fetchColumn();
    if ($id < 1) {
        throw new RuntimeException("Festival-Unterkategorie fehlt oder gehört nicht zu Kategorie 54: {$slug}");
    }
    return $id;
}

function validatedImage(array $record, array &$newFiles): string
{
    if (!$record['image_url']) {
        return '';
    }
    if (!is_dir(IMAGE_DIR) && !mkdir(IMAGE_DIR, 0775, true) && !is_dir(IMAGE_DIR)) {
        throw new RuntimeException('Bildverzeichnis konnte nicht angelegt werden.');
    }
    $destination = IMAGE_DIR . '/' . $record['image_name'];
    if (is_file($destination)) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($destination);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || filesize($destination) < 1024) {
            throw new RuntimeException('Vorhandene Bilddatei ist ungültig: ' . $destination);
        }
        return '/assets/img/uploads/festival-news/' . $record['image_name'];
    }

    $tmp = tempnam(sys_get_temp_dir(), 'festival-news-');
    if ($tmp === false) {
        throw new RuntimeException('Temporäre Bilddatei konnte nicht angelegt werden.');
    }
    $context = stream_context_create(['http' => ['timeout' => 30, 'follow_location' => 1, 'user_agent' => 'The Final Chapter Festival News Importer/1.0']]);
    $data = @file_get_contents($record['image_url'], false, $context);
    if ($data === false || strlen($data) < 1024) {
        @unlink($tmp);
        return '';
    }
    file_put_contents($tmp, $data, LOCK_EX);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        @unlink($tmp);
        return '';
    }
    if (!rename($tmp, $destination)) {
        @unlink($tmp);
        throw new RuntimeException('Validiertes Bild konnte nicht lokal gespeichert werden.');
    }
    $newFiles[] = $destination;
    return '/assets/img/uploads/festival-news/' . $record['image_name'];
}

$db = getDB();
$inserted = [];
$duplicates = [];
$newFiles = [];

try {
    $parent = $db->prepare('SELECT COUNT(*) FROM categories WHERE id = ? AND slug = ?');
    $parent->execute([FESTIVAL_NEWS_PARENT_ID, 'festival-news']);
    if ((int)$parent->fetchColumn() !== 1) {
        throw new RuntimeException('Oberkategorie Festival-News (ID 54) wurde nicht eindeutig gefunden.');
    }

    $db->beginTransaction();
    foreach ($records as $record) {
        $catId = categoryId($db, $record['category_slug']);
        $dupe = $db->prepare('SELECT id, title FROM articles WHERE slug = :slug OR (title = :title AND DATE(created_at) = DATE(:created)) OR content LIKE :source LIMIT 1');
        $dupe->execute([
            ':slug' => $record['slug'],
            ':title' => $record['title'],
            ':created' => $record['date'],
            ':source' => '%' . $record['source_url'] . '%',
        ]);
        $existing = $dupe->fetch();
        if ($existing) {
            $duplicates[] = ['source_url' => $record['source_url'], 'article_id' => (int)$existing['id'], 'title' => $record['title']];
            continue;
        }

        $image = validatedImage($record, $newFiles);
        $stmt = $db->prepare("INSERT INTO articles (title, slug, content, excerpt, category_id, author, featured_image, status, created_at, updated_at) VALUES (:title, :slug, :content, :excerpt, :category_id, 'Michael Jakob', :image, 'published', :created_at, NOW())");
        $stmt->execute([
            ':title' => $record['title'],
            ':slug' => $record['slug'],
            ':content' => $record['content'],
            ':excerpt' => $record['excerpt'],
            ':category_id' => $catId,
            ':image' => $image !== '' ? $image : null,
            ':created_at' => $record['date'],
        ]);
        $inserted[] = ['id' => (int)$db->lastInsertId(), 'title' => $record['title'], 'slug' => $record['slug'], 'category_id' => $catId, 'source_url' => $record['source_url'], 'featured_image' => $image];
    }
    $db->commit();

    $state = ['version' => 1, 'first_cutoff_date' => '2026-07-15', 'sources' => [], 'imported_source_urls' => []];
    if (is_file(STATE_FILE)) {
        $loaded = json_decode((string)file_get_contents(STATE_FILE), true);
        if (is_array($loaded)) {
            $state = array_replace_recursive($state, $loaded);
        }
    }
    foreach ($sources as $key => $source) {
        $state['sources'][$key] = array_merge($source, ['last_successful_checked_at' => $checkedAt]);
    }
    $known = array_fill_keys($state['imported_source_urls'] ?? [], true);
    foreach ($inserted as $row) {
        $known[$row['source_url']] = true;
    }
    foreach ($duplicates as $row) {
        $known[$row['source_url']] = true;
    }
    $state['imported_source_urls'] = array_keys($known);
    sort($state['imported_source_urls']);
    $state['last_successful_run_at'] = $checkedAt;
    $state['last_run_result'] = ['inserted' => count($inserted), 'duplicates' => count($duplicates), 'errors' => 0];
    file_put_contents(STATE_FILE, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, LOCK_EX);

    logLine('Erfolgreicher Lauf: Quellen=' . count($sources) . ', neu=' . count($inserted) . ', Dubletten=' . count($duplicates) . ', Fehler=0');
    echo json_encode(['checked_sources' => array_values($sources), 'inserted' => $inserted, 'duplicates' => $duplicates, 'errors' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    foreach ($newFiles as $file) {
        @unlink($file);
    }
    logLine('FEHLER: ' . $e->getMessage());
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
