<?php
/**
 * The Final Chapter – Neue Artikel als Drafts einfügen (mit Bildern)
 */
ini_set('memory_limit', '256M');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = getDB();
$img_dir = __DIR__ . '/../assets/img/uploads/';
if (!is_dir($img_dir)) mkdir($img_dir, 0755, true);

function download_img(string $url, string $dir): ?string {
    $file = basename(parse_url($url, PHP_URL_PATH));
    if (!$file) return null;
    $path  = $dir . $file;
    $local = '/assets/img/uploads/' . $file;
    if (file_exists($path)) return $local;
    $ctx  = stream_context_create(['http' => ['timeout' => 20, 'user_agent' => 'Mozilla/5.0']]);
    $data = @file_get_contents($url, false, $ctx);
    if (!$data) return null;
    file_put_contents($path, $data);
    return $local;
}

function get_cat_id(PDO $db, string $slug): int {
    $s = $db->prepare("SELECT id FROM categories WHERE slug = ?");
    $s->execute([$slug]);
    $id = $s->fetchColumn();
    if ($id) return (int)$id;
    // anlegen falls nicht vorhanden
    $names = ['news'=>'News','reviews'=>'Reviews','festivals'=>'Festivals','liveberichte'=>'Liveberichte'];
    $name  = $names[$slug] ?? ucfirst($slug);
    $db->prepare("INSERT INTO categories (name,slug) VALUES (?,?)")->execute([$name,$slug]);
    return (int)$db->lastInsertId();
}

$articles = [
  [
    'title'   => 'Farewell, Prince of Darkness – Ozzy Osbourne ist tot',
    'slug'    => 'ozzy-osbourne-verstorben-2025',
    'cat'     => 'news',
    'author'  => 'Michael Jakob',
    'date'    => '2025-07-23 09:00:00',
    'img_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b3/Ozzy_Osbourne_1_crop.jpg/440px-Ozzy_Osbourne_1_crop.jpg',
    'excerpt' => 'Ozzy Osbourne, Mitbegründer von Black Sabbath und lebende Metal-Legende, verstarb am 22. Juli 2025 im Alter von 76 Jahren.',
    'content' => '<p>Ozzy Osbourne, Mitbegründer von Black Sabbath und lebende Metal-Legende, verstarb am 22. Juli 2025 im Alter von 76 Jahren an den Folgen seiner Parkinson-Erkrankung. Nur 17 Tage zuvor hatte er beim monumentalen Benefizkonzert <em>„Back to the Beginning"</em> in Birmingham ein letztes Mal die Bühne betreten — auf einem Thron sitzend, flankiert von Metallica, Guns N\' Roses, Slayer und Tool.</p><p>Das Konzert spielte 190 Millionen US-Dollar für wohltätige Zwecke ein. Die Bilder eines gebrechlichen, aber strahlenden Ozzy auf der Bühne gingen um die Welt — es war ein würdiger Abschied eines Mannes, der das Heavy Metal wie kein anderer geprägt hat.</p><p>Mit Ozzy verliert die Welt nicht nur einen Frontmann, sondern den Vater des Heavy Metal selbst. Von den frühen Black-Sabbath-Tagen über seine legendäre Solokarriere bis hin zu seinem letzten Auftritt — Ozzy Osbourne war, ist und bleibt unsterblich.</p>',
  ],
  [
    'title'   => 'Krushers Of The World – Kreator liefern ihr Thrash-Meisterstück',
    'slug'    => 'kreator-krushers-of-the-world-review-2026',
    'cat'     => 'reviews',
    'author'  => 'Thomas Schwarz',
    'date'    => '2026-01-18 10:00:00',
    'img_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a8/Kreator_-_Wacken_Open_Air_2014_-_08.jpg/440px-Kreator_-_Wacken_Open_Air_2014_-_08.jpg',
    'excerpt' => 'Am 16. Januar 2026 erschien das 16. Studioalbum der Essener Thrash-Titanen — und es schlug ein wie eine Bombe.',
    'content' => '<p>Am 16. Januar 2026 erschien via Nuclear Blast das 16. Studioalbum der Essener Thrash-Titanen — und es schlug ein wie eine Bombe. Produziert von Jens Bogren und mit Cover-Artwork von Zbigniew Bielak (Ghost), präsentieren Kreator auf zehn Tracks ein modernes, kompromissloses Thrash-Monster.</p><p>Songs wie <em>„Seven Serpents"</em> und das düstere <em>„Tränenpalast"</em> (feat. Britta Görtz) beweisen: Mille Petrozza und Co. haben nichts von ihrer Wut verloren. Das Album klingt frisch, aggressiv und zeitgemäß — ohne die Wurzeln zu verleugnen.</p><p>Die anschließende Europa-Tour mit Carcass, Exodus und Nails über 20 Länder war ein weiterer Beweis ihrer anhaltenden Relevanz. <strong>Bewertung: 9/10</strong> — Pflichtwerk für jeden Thrash-Fan.</p>',
  ],
  [
    'title'   => 'The End Of An Era – Megadeth kündigen letztes Album und Abschiedstour an',
    'slug'    => 'megadeth-letztes-album-abschiedstour-2025',
    'cat'     => 'news',
    'author'  => 'Kay Herzer',
    'date'    => '2025-08-15 11:00:00',
    'img_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/57/Megadeth_at_the_2012_Download_Festival.jpg/440px-Megadeth_at_the_2012_Download_Festival.jpg',
    'excerpt' => 'Dave Mustaine überraschte im August 2025 mit der Ankündigung: Das neue Megadeth-Album wird das letzte sein.',
    'content' => '<p>Dave Mustaine überraschte im August 2025 mit einer Ankündigung, die die Metal-Welt erschütterte: Das neue Megadeth-Album wird das letzte sein. Mit Produzent Chris Rakestraw entstand das finale Studiowerk, das im Januar 2026 erschien.</p><p>Die weltweite Farewell-Tour führt die Band auch durch Deutschland, Österreich und die Schweiz. Mustaine sagte: <em>„Wir haben eine Revolution gestartet — jetzt hören wir auf dem Höhepunkt auf."</em></p><p>Für Fans, die Megadeth nie live erlebt haben: Das ist die letzte Chance. Wer noch Tickets möchte, sollte schnell handeln.</p>',
  ],
  [
    'title'   => 'Here I Go No More – David Coverdale beendet seine Karriere',
    'slug'    => 'david-coverdale-whitesnake-karriereende-2025',
    'cat'     => 'news',
    'author'  => 'Michael Jakob',
    'date'    => '2025-11-10 09:30:00',
    'img_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/66/Whitesnake_Loudpark_09.jpg/440px-Whitesnake_Loudpark_09.jpg',
    'excerpt' => 'Im November 2025 verabschiedete sich David Coverdale, Gründer von Whitesnake, offiziell in den Ruhestand.',
    'content' => '<p>Im November 2025 verabschiedete sich David Coverdale, Gründer von Whitesnake und ehemaliger Deep Purple-Frontmann, offiziell in den Ruhestand. Per YouTube-Video wandte sich der 74-Jährige an seine Fans und erklärte, es sei <em>„Zeit, die Rock\'n\'Roll-Plateauschuhe an den Nagel zu hängen"</em>.</p><p>Gesundheitliche Probleme, darunter anhaltende Stimmprobleme, hatten die Band bereits seit 2022 ausgebremst. Damit endet eine über 50-jährige Karriere, die mit Hymnen wie <em>Here I Go Again</em> und <em>Is This Love</em> Musikgeschichte schrieb.</p><p>Coverdale bleibt eine der prägendsten Stimmen des Hard Rock — sein Abgang hinterlässt eine Lücke, die kaum zu füllen sein wird.</p>',
  ],
  [
    'title'   => 'Deep Purple Splat! – Das heavyste Purple-Album seit Jahrzehnten',
    'slug'    => 'deep-purple-splat-album-review-2026',
    'cat'     => 'reviews',
    'author'  => 'Thomas Schwarz',
    'date'    => '2026-07-05 10:00:00',
    'img_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/57/Deep_Purple_-_Wacken_Open_Air_2013_-_Rockfoto-Dschunke_IMG_8617_%28cropped%29.jpg/440px-Deep_Purple_-_Wacken_Open_Air_2013_-_Rockfoto-Dschunke_IMG_8617_%28cropped%29.jpg',
    'excerpt' => 'Am 3. Juli 2026 veröffentlichten Deep Purple ihr 24. Studioalbum Splat! — das heavyste Purple-Album seit Jahrzehnten.',
    'content' => '<p>Am 3. Juli 2026 veröffentlichten Deep Purple ihr 24. Studioalbum <em>Splat!</em> via earMUSIC. Produziert von Bob Ezrin und live im Studio eingespielt, knüpft das Werk direkt an die klassische Sound-DNA der Siebziger an.</p><p>Das Konzeptalbum dreht sich thematisch um das Ende der Menschheit als Transformation — ein reifes, packend umgesetztes Konzept. Roger Glover am Bass und Ian Paice am Schlagzeug klingen so tight wie seit Jahren nicht mehr.</p><p>Die Winter-Europatournee führt die Band im Oktober 2026 u.a. nach Wien, Budapest und Bratislava. <strong>Bewertung: 8/10</strong>.</p>',
  ],
  [
    'title'   => 'Hellfest 2026 – Das Mekka des Metal',
    'slug'    => 'hellfest-2026-rueckblick',
    'cat'     => 'festivals',
    'author'  => 'Kay Herzer',
    'date'    => '2026-06-22 14:00:00',
    'img_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e5/Hellfest_2015.jpg/440px-Hellfest_2015.jpg',
    'excerpt' => 'Vom 18. bis 21. Juni 2026 verwandelte sich Clisson erneut in die Welthauptstadt des Metal.',
    'content' => '<p>Vom 18. bis 21. Juni 2026 verwandelte sich Clisson, Frankreich, erneut in die unangefochtene Welthauptstadt des Metal. Mit 183 bestätigten Bands und Headlinern wie Iron Maiden, Bring Me The Horizon und The Offspring zog das Hellfest fast 200.000 Besucher aus ganz Europa an.</p><p>Helloween feierten ihr 40-jähriges Jubiläum mit einem epischen Sonderset, Mastodon und Opeth lieferten die technischen Highlights. Das emotionale Highlight war das Special Set von Mikkey Dee & Friends mit Motörhead-Klassikern.</p><p>Das Hellfest bleibt das wichtigste Metal-Festival Europas — wer noch nie dort war, sollte 2027 einplanen.</p>',
  ],
  [
    'title'   => 'Graspop Metal Meeting 2026 – Vier Tage, 183 Bands',
    'slug'    => 'graspop-metal-meeting-2026-rueckblick',
    'cat'     => 'festivals',
    'author'  => 'Michael Jakob',
    'date'    => '2026-06-23 12:00:00',
    'img_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f7/Graspop_Metal_Meeting_2009.jpg/440px-Graspop_Metal_Meeting_2009.jpg',
    'excerpt' => 'Das Graspop Metal Meeting 2026 in Dessel, Belgien, präsentierte ein Line-up der Extraklasse.',
    'content' => '<p>Das Graspop Metal Meeting 2026 in Dessel, Belgien, präsentierte ein Line-up der Extraklasse. Headliner waren Bring Me The Horizon, Sabaton, The Offspring und Volbeat — dazu kamen Megadeth, Mastodon, Accept, Def Leppard und Arch Enemy.</p><p>Cavalera spielten das komplette <em>Chaos A.D.</em>-Album live und sorgten für einen der unvergesslichsten Momente des Festivalwochenendes. Mit über 120 bestätigten Bands war es eine der dichtesten Ausgaben der Festivalgeschichte.</p><p>Das Graspop ist für Metal-Fans aus Deutschland eine perfekte Wochenendreise — wir empfehlen es wärmstens.</p>',
  ],
  [
    'title'   => 'Wacken Open Air 2026 – 35 Jahre Heiliger Boden',
    'slug'    => 'wacken-open-air-2026-35-jahre-jubilaeum',
    'cat'     => 'festivals',
    'author'  => 'Thomas Schwarz',
    'date'    => '2026-08-05 18:00:00',
    'img_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/68/Wacken_Open_Air_2013_-_Impressionen_006.jpg/440px-Wacken_Open_Air_2013_-_Impressionen_006.jpg',
    'excerpt' => 'Das W:O:A feiert 2026 sein 35-jähriges Bestehen — mit Judas Priest, Def Leppard und Powerwolf als Headliner.',
    'content' => '<p>Das Wacken Open Air feiert 2026 sein 35-jähriges Bestehen — und das mit einem Lineup, das es in sich hat. Headliner sind Judas Priest, Def Leppard und Powerwolf, die zum ersten Mal als Wacken-Headliner auf der großen Bühne stehen.</p><p>Dazu kommen über 100 weitere Bands: Arch Enemy, Saxon, Black Label Society, In Flames und Paradise Lost. Mit 85.000 Besuchern und dem 35. Jubiläum dürfte 2026 eines der denkwürdigsten Wacken-Jahre aller Zeiten werden.</p><p>Wir sind wie jedes Jahr vor Ort und werden ausführlich berichten. Bis dahin: Wacken ruft, die Metal-Welt antwortet!</p>',
  ],
  [
    'title'   => 'Far From God – Moonspell mit Gothic-Metal-Meisterwerk',
    'slug'    => 'moonspell-far-from-god-review-2026',
    'cat'     => 'reviews',
    'author'  => 'Kay Herzer',
    'date'    => '2026-07-06 10:00:00',
    'img_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e4/Moonspell_-_Fernando_Ribeiro.jpg/440px-Moonspell_-_Fernando_Ribeiro.jpg',
    'excerpt' => 'Nach fünf Jahren Studioschweigen veröffentlichten Moonspell am 3. Juli 2026 ihr neues Album Far From God.',
    'content' => '<p>Nach fünf Jahren Studioschweigen veröffentlichten die portugiesischen Dark-Metal-Pioniere Moonspell am 3. Juli 2026 ihr neues Album <em>Far From God</em> bei Napalm Records. Frontmann Fernando Ribeiro beschreibt es als <em>„melodisches Gothic-Metal-Album voller Melancholie und Schmerz"</em>.</p><p>Das Album überzeugt mit atmosphärischen Gitarren, drückenden Growls und Riberios unverwechselbaren Cleans — ein würdiger Nachfolger zu <em>Hermitage</em> (2021). Ein persönliches, tiefes Album das unter die Haut geht.</p><p><strong>Bewertung: 9/10</strong> — eines der besten Gothic-Metal-Alben seit Jahren.</p>',
  ],
  [
    'title'   => 'Burning Ambition – Iron Maiden kommen ins Kino',
    'slug'    => 'iron-maiden-burning-ambition-kinofilm-2026',
    'cat'     => 'news',
    'author'  => 'Michael Jakob',
    'date'    => '2026-05-07 09:00:00',
    'img_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e9/Iron_Maiden_-_Rock_in_Rio_2019_-_3.jpg/440px-Iron_Maiden_-_Rock_in_Rio_2019_-_3.jpg',
    'excerpt' => 'Am 7. Mai 2026 startete die offizielle Iron Maiden-Dokumentation Burning Ambition in den Kinos weltweit.',
    'content' => '<p>Am 7. Mai 2026 startete die offizielle Iron Maiden-Dokumentation <em>Burning Ambition</em> in den Kinos weltweit. Regisseur Malcolm Venville begleitet die Band auf einer Reise durch fünf Jahrzehnte — von den Pubs im Eastend Londons bis zu ausverkauften Stadien.</p><p>Mit Statements von Lars Ulrich, Chuck D sowie exklusivem Archivmaterial und neuen animierten Eddie-Sequenzen bietet der Film 106 Minuten pures Maiden-Erlebnis. Besonders die frühen 80er-Jahre-Aufnahmen sind eine Zeitkapsel der Metal-Geschichte.</p><p>Gleichzeitig ist die Band mit dem <em>Run For Your Lives World Tour 2026</em> auf der Straße. <em>Burning Ambition</em> ist Pflichtprogramm für jeden Maiden-Fan!</p>',
  ],
  [
    'title'   => '40 Jahre Walls of Jericho – Helloween feiern ihr Jubiläum',
    'slug'    => 'helloween-40-jahre-walls-of-jericho',
    'cat'     => 'news',
    'author'  => 'Thomas Schwarz',
    'date'    => '2025-10-15 10:00:00',
    'img_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/47/Helloween_at_Download_2015.jpg/440px-Helloween_at_Download_2015.jpg',
    'excerpt' => '1985 erschien das Debüt der Hamburger Power-Metal-Legenden — 2025/26 feiern Helloween diesen Meilenstein.',
    'content' => '<p>1985 erschien mit <em>Walls of Jericho</em> das Debüt der Hamburger Power-Metal-Legenden — 40 Jahre später feiern Helloween diesen Meilenstein mit einer weltweiten Jubiläumstournee durch Europa, Asien und Amerika.</p><p>Im Herbst 2025 spielten sie ausverkaufte Shows in Bochum, Hamburg und Stuttgart. Dazu erschien die Deluxe-Anthologie <em>March Of Time – The Best Of 40 Years</em> mit 42 kuratierten Tracks.</p><p>Beim Hellfest 2026 lieferten sie einen epischen Jubiläums-Set mit Klassikern aus allen 16 Studioalben. Pumpkins Forever! 🎃</p>',
  ],
  [
    'title'   => 'Greg Puciato verlässt Better Lovers',
    'slug'    => 'greg-puciato-verlässt-better-lovers-2026',
    'cat'     => 'news',
    'author'  => 'Kay Herzer',
    'date'    => '2026-02-10 11:00:00',
    'img_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3c/Every_Time_I_Die_at_Warped_Tour_2009.jpg/440px-Every_Time_I_Die_at_Warped_Tour_2009.jpg',
    'excerpt' => 'Im Februar 2026 gaben Better Lovers bekannt: Sänger Greg Puciato verlässt die Band.',
    'content' => '<p>Im Februar 2026 gaben Better Lovers bekannt: Sänger Greg Puciato verlässt die Band. Die 2023 gegründete Formation — bestehend aus ehemaligen Every Time I Die-Mitgliedern — erklärte, man bewege sich <em>„in eine andere Richtung"</em>.</p><p>Puciato kommentierte sachlich: <em>„No bad blood. Everyone\'s cool."</em> Der Split trifft die Hardcore-Community besonders hart, da Better Lovers erst 2024 ihr gefeiertes Debüt <em>Highly Irresponsible</em> veröffentlicht hatten.</p><p>Wie es mit Puciato weitergeht, ist offen — sein Soloprojekt und seine Verbindungen zur Dillinger Escape Plan-Alumni-Szene lassen vermuten, dass er nicht lange untätig bleiben wird.</p>',
  ],
  [
    'title'   => 'Liturgy of Death – Mayhem mit neuem Black-Metal-Manifest',
    'slug'    => 'mayhem-liturgy-of-death-review-2026',
    'cat'     => 'reviews',
    'author'  => 'Michael Jakob',
    'date'    => '2026-02-08 09:00:00',
    'img_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/Mayhem_live_at_Inferno_festival_2009_01.jpg/440px-Mayhem_live_at_Inferno_festival_2009_01.jpg',
    'excerpt' => 'Die norwegischen Black-Metal-Urväter Mayhem veröffentlichten ihr siebtes Studioalbum Liturgy of Death.',
    'content' => '<p>Am 6. Februar 2026 veröffentlichten die norwegischen Black-Metal-Urväter Mayhem ihr siebtes Studioalbum <em>Liturgy of Death</em>. Die berühmt-berüchtigte Band präsentiert sich kompromisslos und zeitlos düster.</p><p>Fans der frühen Alben werden die rohe Energie wiedererkennen, während das Songwriting moderner und strukturierter wirkt als auf manchem Vorgänger. Die Produktion ist dreckig, aber bewusst — so wie es sich für Mayhem gehört.</p><p>Beim Hellfest 2026 und dem Wacken Open Air gehörten sie zu den eindrucksvollsten Acts des Jahres. <strong>Bewertung: 8/10</strong>.</p>',
  ],
  [
    'title'   => 'Ginger von den Wildhearts gibt Krebsdiagnose bekannt',
    'slug'    => 'ginger-wildhearts-krebsdiagnose-2026',
    'cat'     => 'news',
    'author'  => 'Thomas Schwarz',
    'date'    => '2026-06-15 10:00:00',
    'img_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5b/The_Wildhearts_at_Download_2011.jpg/440px-The_Wildhearts_at_Download_2011.jpg',
    'excerpt' => 'Ginger, Frontmann der Wildhearts, hat im Juni 2026 öffentlich über seine Krebsdiagnose gesprochen.',
    'content' => '<p>Eine persönliche und bewegende Meldung aus der britischen Rock-Szene: Ginger, Frontmann der Wildhearts, hat im Juni 2026 öffentlich über seine Krebsdiagnose gesprochen. <em>„Ich lebe jeden Moment mit vollster Aufmerksamkeit"</em>, sagte er in einem Statement.</p><p>Die Wildhearts zählen seit den frühen 90ern zu den einflussreichsten britischen Hard-Rock-Bands. Gingers ehrliche Worte lösten eine massive Welle der Solidarität in der gesamten Metal- und Rock-Community aus.</p><p>Wir wünschen Ginger von Herzen alles Gute und schnelle Genesung. Die Metal-Familie steht zusammen!</p>',
  ],
  [
    'title'   => 'Trauer um Barth Resch – Belphegor-Bassist verstorben',
    'slug'    => 'barth-resch-belphegor-verstorben-2026',
    'cat'     => 'news',
    'author'  => 'Kay Herzer',
    'date'    => '2026-06-20 08:00:00',
    'img_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a2/Belphegor_live_2014.jpg/440px-Belphegor_live_2014.jpg',
    'excerpt' => 'Mit tiefer Trauer vermeldete die Metal-Community den Tod von Belphegor-Bassist Bartholomäus Resch.',
    'content' => '<p>Mit tiefer Trauer vermeldete die Metal-Community im Juni 2026 den Tod von Bartholomäus „Barth" Resch, dem ehemaligen Bassisten der österreichischen Black/Death-Legenden Belphegor. Er wurde nur 49 Jahre alt.</p><p>Resch war ein prägendes Mitglied der Salzburger Band in deren frühen Jahren und hinterließ ein Erbe, das den extremen Metal-Untergrund Österreichs nachhaltig mitgeprägt hat.</p><p>Sein Tod ist ein weiterer schwerer Verlust für die Metal-Welt — gerade nachdem 2025 bereits mit dem Tod Ozzy Osbournes einen kaum fassbaren Einschnitt erlebt hat. Rest in Metal, Barth.</p>',
  ],
];

$ok = 0; $skip = 0; $img_ok = 0;

foreach ($articles as $a) {
    // Duplikat-Check
    $chk = $db->prepare("SELECT id FROM articles WHERE slug = ?");
    $chk->execute([$a['slug']]);
    if ($chk->fetchColumn()) { $skip++; continue; }

    $cat_id = get_cat_id($db, $a['cat']);

    // Bild herunterladen
    $img = download_img($a['img_url'], $img_dir);
    if ($img) $img_ok++;

    $db->prepare("INSERT INTO articles
        (title, slug, content, excerpt, category_id, author, featured_image, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', ?)")
    ->execute([
        $a['title'], $a['slug'], $a['content'], $a['excerpt'],
        $cat_id, $a['author'], $img, $a['date']
    ]);
    $ok++;
    echo "  ✓ " . $a['title'] . "\n";
}

echo "\n------------------------------------------------------------\n";
echo "Neu angelegt : $ok\n";
echo "Übersprungen : $skip (Duplikate)\n";
echo "Bilder OK    : $img_ok\n";
echo "Status       : draft (nicht veröffentlicht)\n";
echo "------------------------------------------------------------\n";
