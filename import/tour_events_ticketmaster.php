<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$apiKey = getenv('TICKETMASTER_API_KEY') ?: '';
if ($apiKey === '') {
    fwrite(STDERR, "TICKETMASTER_API_KEY fehlt. Import abgebrochen.\n");
    exit(2);
}

if (!tourEventsTableExists()) {
    fwrite(STDERR, "tour_events Tabelle fehlt. Migration zuerst ausführen.\n");
    exit(3);
}

function envCsv(string $name, string $fallback = ''): array
{
    $value = getenv($name);
    if ($value === false || trim($value) === '') {
        $value = $fallback;
    }
    return array_values(array_unique(array_filter(array_map('trim', explode(',', $value)))));
}

$defaultArtists = implode(',', [
    // TFC-Kern: große und regelmäßig tourende Metal-/Hard-Rock-Acts mit Relevanz für DE/AT/CH.
    'Kreator', 'Sodom', 'Destruction', 'Tankard', 'Accept', 'U.D.O.', 'Dirkschneider', 'Doro', 'Helloween', 'Gamma Ray',
    'Blind Guardian', 'Avantasia', 'Edguy', 'Powerwolf', 'Heaven Shall Burn', 'Caliban', 'Any Given Day', 'Electric Callboy',
    'Beyond The Black', 'Lord Of The Lost', 'Oomph!', 'Eisbrecher', 'Mono Inc.', 'Subway To Sally', 'In Extremo',
    'Saltatio Mortis', 'Feuerschwanz', 'Schandmaul', 'Die Apokalyptischen Reiter', 'Equilibrium', 'Finsterforst',
    'Kanonenfieber', 'Der Weg einer Freiheit', 'The Ocean', 'Kadavar', 'Long Distance Calling', 'Rage', 'Grave Digger',
    'Primal Fear', 'Brainstorm', 'Orden Ogan', 'Freedom Call', 'Kissin Dynamite', 'The New Roses', 'Bonfire', 'J.B.O.',

    // Skandinavien / Pagan / Melodic / Death / Black – für TFC-Festival- und Clubumfeld wichtig.
    'Amon Amarth', 'Arch Enemy', 'In Flames', 'Dark Tranquillity', 'At The Gates', 'The Halo Effect', 'Soilwork',
    'Hypocrisy', 'Pain', 'Amorphis', 'Insomnium', 'Omnium Gatherum', 'Swallow The Sun', 'Ensiferum', 'Moonsorrow',
    'Korpiklaani', 'Finntroll', 'Turisas', 'Eluveitie', 'Cellar Darling', 'Nightwish', 'Tarja', 'Within Temptation',
    'Epica', 'Delain', 'Lacuna Coil', 'Apocalyptica', 'Children Of Bodom', 'Bodom After Midnight', 'Marduk',
    'Watain', 'Dark Funeral', 'Mayhem', 'Dimmu Borgir', 'Enslaved', 'Satyricon', 'Borknagar', 'Kampfar', 'Taake',
    'Rotting Christ', 'Septicflesh', 'Primordial', 'Skyforger', 'Thyrfing', 'Varg', 'Nachtblut', 'Eis', 'Firtan',

    // International regelmäßig in DE/AT/CH unterwegs.
    'Machine Head', 'Trivium', 'Testament', 'Exodus', 'Overkill', 'Anthrax', 'Megadeth', 'Slayer', 'Lamb Of God',
    'Mastodon', 'Gojira', 'Opeth', 'Meshuggah', 'Dream Theater', 'Queensryche', 'Fates Warning', 'Kamelot', 'Sonata Arctica',
    'Stratovarius', 'HammerFall', 'Sabaton', 'Avatar', 'Ghost', 'Behemoth', 'Decapitated', 'Vader', 'Nile', 'Obituary',
    'Cannibal Corpse', 'Morbid Angel', 'Deicide', 'Napalm Death', 'Carcass', 'Paradise Lost', 'My Dying Bride', 'Anathema',
    'Cradle Of Filth', 'Alestorm', 'Gloryhammer', 'Wind Rose', 'Brothers Of Metal', 'Battle Beast', 'Beast In Black',
    'Black Stone Cherry', 'Airbourne', 'Steel Panther', 'Skid Row', 'Europe', 'W.A.S.P.', 'Alice Cooper'
]);

$allArtists = envCsv('TFC_TOUR_ARTISTS', $defaultArtists);
$artistOffset = max(0, (int)(getenv('TFC_TOUR_ARTIST_OFFSET') ?: 0));
$artistLimit = (int)(getenv('TFC_TOUR_ARTIST_LIMIT') ?: 0);
$artists = $artistLimit > 0 ? array_slice($allArtists, $artistOffset, $artistLimit) : $allArtists;
$countries = envCsv('TFC_TOUR_COUNTRY', 'DE,AT,CH');
$keywords = envCsv('TFC_TOUR_KEYWORDS', getenv('TFC_TOUR_KEYWORD_MODE') ? 'metal,heavy metal,rock festival,metal festival' : '');
$pages = max(1, min(5, (int)(getenv('TFC_TOUR_PAGES') ?: 1)));
$size = max(1, min(200, (int)(getenv('TFC_TOUR_SIZE') ?: 50)));

$db = getDB();
$upsert = $db->prepare(
    'INSERT INTO tour_events (artist, event_title, venue, city, region, country, starts_at, ticket_url, source, source_event_id, status, last_seen_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, "ticketmaster", ?, "published", NOW())
     ON DUPLICATE KEY UPDATE
       artist=VALUES(artist), event_title=VALUES(event_title), venue=VALUES(venue), city=VALUES(city), region=VALUES(region), country=VALUES(country), starts_at=VALUES(starts_at), ticket_url=VALUES(ticket_url), status="published", last_seen_at=NOW()'
);

$queries = [];
foreach ($artists as $artist) {
    foreach ($countries as $countryCode) {
        $queries[] = ['type' => 'artist', 'keyword' => $artist, 'country' => strtoupper($countryCode)];
    }
}
foreach ($keywords as $keyword) {
    foreach ($countries as $countryCode) {
        $queries[] = ['type' => 'keyword', 'keyword' => $keyword, 'country' => strtoupper($countryCode)];
    }
}

$seen = [];
$imported = 0;
$updatedOrSeen = 0;
$failed = 0;
$perQuery = [];
$httpContext = stream_context_create([
    'http' => [
        'timeout' => max(3, min(20, (int)(getenv('TFC_TOUR_HTTP_TIMEOUT') ?: 8))),
        'ignore_errors' => true,
        'header' => "User-Agent: TheFinalChapterTourImporter/1.0\r\nAccept: application/json\r\n",
    ],
]);
if (function_exists('set_time_limit')) {
    @set_time_limit(max(20, min(120, (int)(getenv('TFC_TOUR_MAX_SECONDS') ?: 30))));
}

foreach ($queries as $query) {
    $queryImported = 0;
    for ($page = 0; $page < $pages; $page++) {
        $url = 'https://app.ticketmaster.com/discovery/v2/events.json?' . http_build_query([
            'apikey' => $apiKey,
            'keyword' => $query['keyword'],
            'countryCode' => $query['country'],
            'classificationName' => 'music',
            'size' => $size,
            'page' => $page,
            'sort' => 'date,asc',
        ]);
        $json = @file_get_contents($url, false, $httpContext);
        if ($json === false) {
            $failed++;
            fwrite(STDERR, "Ticketmaster Abruf fehlgeschlagen: {$query['keyword']} ({$query['country']}, Seite {$page})\n");
            continue;
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            $failed++;
            fwrite(STDERR, "Ticketmaster Antwort ungültig: {$query['keyword']} ({$query['country']}, Seite {$page})\n");
            continue;
        }
        $events = $data['_embedded']['events'] ?? [];
        foreach ($events as $event) {
            $eventId = (string)($event['id'] ?? '');
            $date = (string)($event['dates']['start']['localDate'] ?? '');
            $time = (string)($event['dates']['start']['localTime'] ?? '20:00:00');
            if ($eventId === '' || $date === '' || isset($seen[$eventId])) {
                continue;
            }
            $seen[$eventId] = true;
            $venue = $event['_embedded']['venues'][0] ?? [];
            $attractions = $event['_embedded']['attractions'] ?? [];
            $artistName = $query['type'] === 'artist' ? $query['keyword'] : (string)($attractions[0]['name'] ?? $event['name'] ?? $query['keyword']);
            $startsAt = $date . ' ' . substr($time, 0, 8);
            $upsert->execute([
                $artistName,
                (string)($event['name'] ?? $artistName),
                (string)($venue['name'] ?? ''),
                (string)($venue['city']['name'] ?? ''),
                (string)($venue['state']['name'] ?? ''),
                (string)($venue['country']['countryCode'] ?? $query['country']),
                $startsAt,
                normalizeTourEventUrl((string)($event['url'] ?? '')),
                $eventId,
            ]);
            $imported++;
            $queryImported++;
        }
        if (($data['page']['totalPages'] ?? 1) <= $page + 1) {
            break;
        }
    }
    if ($queryImported > 0) {
        $perQuery[] = "{$query['keyword']} {$query['country']}: {$queryImported}";
    }
    $updatedOrSeen += $queryImported;
}

echo "OK: {$imported} Tourdaten importiert/aktualisiert.\n";
echo "Batch: Offset {$artistOffset}, Limit " . ($artistLimit > 0 ? $artistLimit : 'alle') . ", Künstler in diesem Lauf: " . count($artists) . " von " . count($allArtists) . ".\n";
echo "Abfragen: " . count($queries) . ", Länder: " . implode('/', $countries) . ", Künstler: " . count($artists) . ", Keywords: " . count($keywords) . ", Fehler: {$failed}.\n";
if ($perQuery) {
    echo "Treffer: " . implode('; ', $perQuery) . "\n";
}
