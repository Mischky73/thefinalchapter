<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = getDB();
$category = $db->prepare("SELECT id FROM categories WHERE slug = ? LIMIT 1");
$category->execute(['in-flammen']);
$categoryId = (int)$category->fetchColumn();
if (!$categoryId) {
    throw new RuntimeException('Unterkategorie In Flammen nicht gefunden.');
}

$official = 'https://www.in-flammen.com/';
$news = [
    [
        'title' => 'In Flammen 2025: Vorverkauf geht in die Schlussphase',
        'slug' => 'in-flammen-2025-vorverkauf-schlussphase-41-bands',
        'date' => '2025-06-19 18:19:55',
        'image' => '/assets/img/uploads/in-flammen/in-flammen-logo.jpg',
        'excerpt' => 'Kurz vor dem In Flammen Open Air 2025 kündigten die Veranstalter das Ende des Vorverkaufs an. Insgesamt waren 41 Bands bestätigt.',
        'content' => '<p>Das In Flammen Open Air 2025 ging im Juni in die heiße Vorbereitungsphase. Die Veranstalter wiesen darauf hin, dass der Vorverkauf nur noch wenige Tage läuft. Für die Ausgabe vom 10. bis 12. Juli 2025 am Entenfang in Torgau waren zu diesem Zeitpunkt 41 Bands bestätigt.</p><p>Das Festival blieb damit seinem Konzept als kompakte „Hellish Gartenparty“ treu: drei Tage Metal ohne großes Rahmenprogramm, dafür mit einem umfangreichen internationalen Line-up.</p><p><strong>Quelle:</strong> <a href="https://web.archive.org/web/20250619181955/https://www.in-flammen.com/" target="_blank" rel="noopener">Offizielle Festivalmeldung, archiviert am 19. Juni 2025</a></p>',
    ],
    [
        'title' => 'In Flammen kündigt Jubiläumsausgabe 2026 in Torgau an',
        'slug' => 'in-flammen-kuendigt-jubilaeumsausgabe-2026-an',
        'date' => '2025-08-11 16:37:17',
        'image' => '/assets/img/uploads/in-flammen/in-flammen-logo.jpg',
        'excerpt' => 'Die Jubiläumsausgabe des In Flammen Open Air wurde für den 9. bis 11. Juli 2026 am Entenfang in Torgau angekündigt.',
        'content' => '<p>Nach der Ausgabe 2025 richtete das In Flammen Open Air den Blick auf sein 20-jähriges Jubiläum. Die nächste „Hellish Gartenparty“ wurde für den 9. bis 11. Juli 2026 am Entenfang in Torgau angekündigt.</p><p>Gleichzeitig startete der Verkauf der auf jeweils 666 Stück begrenzten Early-Bird-Kategorien. Damit begann knapp elf Monate vor dem Festival die Vorbereitung der Jubiläumsausgabe.</p><p><strong>Quelle:</strong> <a href="https://web.archive.org/web/20250811163717/https://www.in-flammen.com/" target="_blank" rel="noopener">Offizielle Festivalmeldung, archiviert am 11. August 2025</a></p>',
    ],
    [
        'title' => 'In Flammen 2026: Early-Bird-Kategorie 1 fast vergriffen',
        'slug' => 'in-flammen-2026-early-bird-kategorie-1-fast-vergriffen',
        'date' => '2025-08-11 16:40:00',
        'image' => '/assets/img/uploads/in-flammen/in-flammen-logo.jpg',
        'excerpt' => 'Von der ersten Early-Bird-Kategorie für das In Flammen 2026 war nur noch eine kleine Restmenge verfügbar.',
        'content' => '<p>Der frühe Ticketverkauf für das In Flammen Open Air 2026 nahm schnell Fahrt auf. Nach Angaben des Festivals war von der ersten Early-Bird-Kategorie bereits nur noch eine kleine Restmenge verfügbar.</p><p>Jede der angebotenen Early-Bird-Kategorien war auf 666 Tickets begrenzt. Das Jubiläumsfestival sollte vom 9. bis 11. Juli 2026 in Torgau stattfinden.</p><p><strong>Quelle:</strong> <a href="https://web.archive.org/web/20250811163717/https://www.in-flammen.com/" target="_blank" rel="noopener">Offizielle Festivalmeldung, archiviert am 11. August 2025</a></p>',
    ],
    [
        'title' => 'In Flammen 2026: Nur noch Early-Bird-Kategorie 2 erhältlich',
        'slug' => 'in-flammen-2026-nur-noch-early-bird-kategorie-2',
        'date' => '2025-08-25 05:57:57',
        'image' => '/assets/img/uploads/in-flammen/in-flammen-logo.jpg',
        'excerpt' => 'Die erste Early-Bird-Stufe war ausverkauft. Verfügbar blieb zunächst nur noch die zweite Kategorie.',
        'content' => '<p>Nur zwei Wochen nach der ersten Verkaufsmeldung war die günstigste Early-Bird-Stufe für das In Flammen Open Air 2026 vergriffen. Das Festival meldete, dass nur noch Tickets der zweiten Early-Bird-Kategorie erhältlich seien.</p><p>Auch diese Stufe war auf 666 Stück limitiert. Die frühe Nachfrage unterstrich das große Interesse an der 20. Ausgabe der Torgauer „Hellish Gartenparty“.</p><p><strong>Quelle:</strong> <a href="https://web.archive.org/web/20250825055757/https://www.in-flammen.com/" target="_blank" rel="noopener">Offizielle Festivalmeldung, archiviert am 25. August 2025</a></p>',
    ],
    [
        'title' => 'Grave für das In Flammen Open Air 2026 bestätigt',
        'slug' => 'grave-fuer-in-flammen-open-air-2026-bestaetigt',
        'date' => '2026-01-01 12:57:08',
        'image' => '/assets/img/uploads/in-flammen/in-flammen-2026-final-lineup.jpg',
        'excerpt' => 'Die schwedischen Death-Metal-Veteranen Grave wurden für die Jubiläumsausgabe in Torgau angekündigt.',
        'content' => '<p>Das In Flammen Open Air verstärkte sein Jubiläumsprogramm mit schwedischem Death Metal: Grave wurden für die Ausgabe 2026 am Entenfang in Torgau bestätigt.</p><p>Die traditionsreiche Band ergänzte das internationale Aufgebot der dreitägigen Gartenparty. Das Festival war für den 9. bis 11. Juli 2026 angesetzt.</p><p><strong>Quelle:</strong> <a href="https://web.archive.org/web/20260101125708/https://www.in-flammen.com/" target="_blank" rel="noopener">Offizielle Festivalmeldung, archiviert am 1. Januar 2026</a></p>',
    ],
    [
        'title' => 'In Flammen 2026 meldet 65 Prozent verkaufte Tickets',
        'slug' => 'in-flammen-2026-65-prozent-tickets-verkauft',
        'date' => '2026-01-01 13:00:00',
        'image' => '/assets/img/uploads/in-flammen/in-flammen-logo.jpg',
        'excerpt' => 'Bereits zum Jahresbeginn waren nach Angaben des Festivals 65 Prozent aller Eintrittskarten vergeben.',
        'content' => '<p>Das Interesse an der 20. Ausgabe des In Flammen Open Air blieb hoch. Zum Jahresbeginn 2026 meldeten die Veranstalter, dass bereits 65 Prozent aller verfügbaren Tickets verkauft waren.</p><p>Die Jubiläumsausgabe sollte vom 9. bis 11. Juli 2026 am Entenfang in Torgau stattfinden. Schon im August 2025 war die erste Early-Bird-Kategorie vergriffen.</p><p><strong>Quelle:</strong> <a href="https://web.archive.org/web/20260101125708/https://www.in-flammen.com/" target="_blank" rel="noopener">Offizielle Festivalmeldung, archiviert am 1. Januar 2026</a></p>',
    ],
    [
        'title' => 'Drudensang und Hangover in Minsk beim In Flammen 2026',
        'slug' => 'drudensang-hangover-in-minsk-in-flammen-2026',
        'date' => '2026-01-18 12:24:16',
        'image' => '/assets/img/uploads/in-flammen/drudensang-2026.jpg',
        'excerpt' => 'Drudensang und Hangover in Minsk erweiterten das Line-up der Jubiläumsausgabe in Torgau.',
        'content' => '<p>Das Line-up des In Flammen Open Air 2026 nahm im Januar weiter Gestalt an. Mit Drudensang und Hangover in Minsk bestätigte das Festival zwei weitere Acts für die 20. Ausgabe.</p><p>Beide Namen tauchten in den offiziellen Ankündigungsmotiven des Festivals auf und wurden später auch in der veröffentlichten Line-up-Welle geführt. Das Jubiläum war für den 9. bis 11. Juli 2026 angesetzt.</p><p><strong>Quellen:</strong> <a href="https://www.in-flammen.com/" target="_blank" rel="noopener">In Flammen Open Air</a>, offizielle Ankündigungsmotive vom 18. Januar 2026</p>',
    ],
    [
        'title' => 'The Crown spielen Abschiedsshow beim In Flammen 2026',
        'slug' => 'the-crown-abschiedsshow-in-flammen-2026',
        'date' => '2026-01-24 19:36:02',
        'image' => '/assets/img/uploads/in-flammen/the-crown-2026.jpg',
        'excerpt' => 'The Crown kündigten für Torgau ihre einzige und letzte Festivalshow in Deutschland an.',
        'content' => '<p>Ein besonderer Termin für Death-Metal-Fans: The Crown wurden für das In Flammen Open Air 2026 mit einer Farewell Show angekündigt. Nach 35 Jahren sollte die schwedische Band in Torgau ihre einzige und letzte Festivalshow in Deutschland spielen.</p><p>Damit erhielt die Jubiläumsausgabe einen Auftritt mit besonderem Abschiedscharakter. Das Festival lief vom 9. bis 11. Juli 2026 am Entenfang.</p><p><strong>Quellen:</strong> <a href="https://www.in-flammen.com/" target="_blank" rel="noopener">Offizielle Festivalmeldung</a> und offizielles Ankündigungsmotiv vom 24. Januar 2026</p>',
    ],
    [
        'title' => 'Satyricon mit exklusiver 90-Minuten-Show beim In Flammen',
        'slug' => 'satyricon-exklusive-show-in-flammen-2026',
        'date' => '2026-02-02 21:15:07',
        'image' => '/assets/img/uploads/in-flammen/satyricon-2026.jpg',
        'excerpt' => 'Satyricon wurden mit einer 90-minütigen Special Show als zentraler Act des In Flammen Open Air 2026 bestätigt.',
        'content' => '<p>Das In Flammen Open Air holte Satyricon zur Jubiläumsausgabe nach Torgau. Angekündigt wurde eine 90-minütige Special Show, zu der auch „Mother North“ und zahlreiche weitere Klassiker gehören sollten.</p><p>Nach Angaben des Festivals handelte es sich 2026 um die einzige Show der Norweger in dieser Form in den hiesigen Breitengraden. Satyricon standen damit an der Spitze des Jubiläumsprogramms.</p><p><strong>Quellen:</strong> <a href="https://www.in-flammen.com/" target="_blank" rel="noopener">Offizielle Festivalmeldung</a> und offizielles Ankündigungsmotiv vom 2. Februar 2026</p>',
    ],
    [
        'title' => 'Neue Bandwelle für das In Flammen 2026',
        'slug' => 'in-flammen-2026-neue-bandwelle-sigh-primordial-hellripper',
        'date' => '2026-02-06 21:56:51',
        'image' => '/assets/img/uploads/in-flammen/sigh-2026.jpg',
        'excerpt' => 'Sigh, Primordial, Hellripper und weitere Bands wurden für die Jubiläumsausgabe in Torgau angekündigt.',
        'content' => '<p>Das In Flammen Open Air legte Anfang Februar mit einer weiteren Bandwelle nach. Zu den offiziell präsentierten Namen gehörten die japanische Black-Metal-Legende Sigh sowie Primordial und Hellripper.</p><p>Die neuen Bestätigungen verbreiterten das Programm zwischen Black, Death und Heavy Metal deutlich. In weiteren offiziellen Motiven wurden zudem zusätzliche internationale Acts für die Jubiläumsausgabe vorgestellt.</p><p><strong>Quellen:</strong> <a href="https://www.in-flammen.com/" target="_blank" rel="noopener">In Flammen Open Air</a>, offizielle Ankündigungsmotive vom 6. Februar 2026</p>',
    ],
    [
        'title' => 'Ancient für das In Flammen Open Air 2026 bestätigt',
        'slug' => 'ancient-fuer-in-flammen-open-air-2026-bestaetigt',
        'date' => '2026-03-09 19:51:40',
        'image' => '/assets/img/uploads/in-flammen/in-flammen-2026-final-lineup.jpg',
        'excerpt' => 'Ancient erweiterten im März das internationale Black-Metal-Aufgebot der Jubiläumsausgabe.',
        'content' => '<p>Mit Ancient bestätigte das In Flammen Open Air einen weiteren international bekannten Black-Metal-Act für 2026. Die Band wurde Anfang März über ein offizielles Festivalmotiv angekündigt.</p><p>Ancient ergänzten ein Programm, das zu diesem Zeitpunkt bereits Satyricon, Sigh, Primordial, The Crown, Grave und zahlreiche weitere Bands umfasste.</p><p><strong>Quellen:</strong> <a href="https://www.in-flammen.com/" target="_blank" rel="noopener">In Flammen Open Air</a>, offizielles Ankündigungsmotiv vom 9. März 2026</p>',
    ],
    [
        'title' => 'In Flammen 2026 macht das Jubiläums-Line-up komplett',
        'slug' => 'in-flammen-2026-finales-jubilaeums-lineup',
        'date' => '2026-06-21 11:42:50',
        'image' => '/assets/img/uploads/in-flammen/in-flammen-2026-final-lineup.jpg',
        'excerpt' => 'Das vollständige Line-up für das 20-jährige Jubiläum in Torgau stand fest. Satyricon führten das Programm an.',
        'content' => '<p>Das In Flammen Open Air meldete Vollzug: Alle Bands für die Jubiläumsausgabe 2026 waren bestätigt. An der Spitze des Programms standen Satyricon, dazu kamen unter anderem Primordial, Grave, Macabre, Asphyx, The Crown, Hate, Ancient, Hellripper, Sigh, Drudensang und Hangover in Minsk.</p><p>Die 20. Ausgabe der „Hellish Gartenparty“ fand vom 9. bis 11. Juli 2026 am Entenfang in Torgau statt. Das finale Plakat bündelte das umfangreiche internationale Aufgebot.</p><p><strong>Quelle:</strong> <a href="https://www.in-flammen.com/" target="_blank" rel="noopener">Offizielle Festival-Website und finales Line-up-Plakat</a></p>',
    ],
    [
        'title' => 'In Flammen 2026: Anreise, Camping und Sicherheitsregeln',
        'slug' => 'in-flammen-2026-anreise-camping-sicherheitsregeln',
        'date' => '2026-07-08 12:00:00',
        'image' => '/assets/img/uploads/in-flammen/in-flammen-logo.jpg',
        'excerpt' => 'Die Veranstalter veröffentlichten wichtige Hinweise zur Anreise und zu den Regeln auf dem Festivalgelände am Entenfang.',
        'content' => '<p>Für die Anreise zum In Flammen Open Air 2026 galt: Der Zugang zum Gelände am Entenfang war erst am Donnerstag, 9. Juli, ab 10 Uhr möglich. Als Festivaladresse wurde Entenfang 1 in 04860 Torgau genannt.</p><p>Auf dem Gelände waren Grills, Gasflaschen, offene Feuer, Stromaggregate, Glas und Waffen untersagt. Kleine Campingkocher blieben erlaubt. Vor Ort standen außerdem ein Bereich mit Duschen und festen Toiletten zur Verfügung.</p><p><strong>Quelle:</strong> <a href="https://www.in-flammen.com/infos/" target="_blank" rel="noopener">Offizielle Besucherinformationen des In Flammen Open Air</a></p>',
    ],
    [
        'title' => 'In Flammen 2027: Early-Bird-Tickets ab sofort erhältlich',
        'slug' => 'in-flammen-2027-early-bird-tickets-erhaeltlich',
        'date' => '2026-07-15 08:00:00',
        'image' => '/assets/img/uploads/in-flammen/in-flammen-logo.jpg',
        'excerpt' => 'Nach der Jubiläumsausgabe startete der Vorverkauf für das In Flammen Open Air vom 15. bis 17. Juli 2027.',
        'content' => '<p>Das nächste In Flammen Open Air ist terminiert: Die „Hellish Gartenparty“ soll vom 15. bis 17. Juli 2027 erneut am Entenfang in Torgau stattfinden. Der Verkauf der Early-Bird-Tickets wurde bereits eröffnet.</p><p>Im offiziellen Shop werden neben regulären Weekend-Tickets auch Varianten mit Sanitärzugang sowie zusätzliche Angebote geführt. Die Early-Bird-Kategorie ist erneut auf insgesamt 666 Stück begrenzt.</p><p><strong>Quellen:</strong> <a href="https://www.in-flammen.com/" target="_blank" rel="noopener">Offizielle Festivalmeldung</a>, <a href="https://www.in-flammen.com/tickets-more/" target="_blank" rel="noopener">offizieller Ticketshop</a></p>',
    ],
];

$sql = 'INSERT INTO articles
        (title, slug, content, excerpt, category_id, author, featured_image, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, "draft", ?, NOW())
        ON DUPLICATE KEY UPDATE
          title=VALUES(title), content=VALUES(content), excerpt=VALUES(excerpt),
          category_id=VALUES(category_id), author=VALUES(author),
          featured_image=VALUES(featured_image), status="draft", created_at=VALUES(created_at), updated_at=NOW()';
$stmt = $db->prepare($sql);

$db->beginTransaction();
try {
    foreach ($news as $item) {
        $stmt->execute([
            $item['title'], $item['slug'], $item['content'], $item['excerpt'],
            $categoryId, 'Redaktion', $item['image'], $item['date'],
        ]);
    }
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}

$count = $db->prepare('SELECT COUNT(*) FROM articles WHERE category_id=? AND status="draft"');
$count->execute([$categoryId]);
printf("In Flammen News als Entwürfe gespeichert: %d\n", (int)$count->fetchColumn());
