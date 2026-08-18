<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';

$pdo = getDB();
$category = $pdo->prepare('SELECT id FROM categories WHERE slug = ? LIMIT 1');
$category->execute(['festivals']);
$categoryId = (int)$category->fetchColumn();
if (!$categoryId) {
    throw new RuntimeException('Kategorie Festivals nicht gefunden.');
}

$title = 'Party.San Open Air 2024: Schwarze Messen und Feuer über Obermehler';
$slug = 'party-san-open-air-2024-festivalbericht';
$excerpt = 'Vom 8. bis 10. August 2024 regierten auf dem Flugplatz Obermehler Death, Black, Doom und Thrash Metal. Der große Rückblick auf 56 Auftritte und drei kompromisslose Festivaltage.';
$image = '/assets/img/uploads/party-san/party-san-open-air-2024-offizieller-flyer.jpg';
$content = <<<'HTML'
<p><strong>Vom 8. bis 10. August 2024 wurde der Flugplatz Obermehler bei Schlotheim wieder zum Zentrum des europäischen Extrem Metal.</strong> Das Party.San Open Air ging in seine 28. Ausgabe und lieferte auf Mainstage und Tentstage eine offizielle Running Order mit 56 Auftritten. Death und Black Metal bildeten das Fundament, doch Doom, Thrash, Grindcore, Gothic Rock und traditioneller Heavy Metal sorgten dafür, dass die drei Tage nicht zu einer gleichförmigen Dauerbeschallung wurden.</p>

<p>Die Bilder aus der offiziellen Festivalgalerie und dem Aftermovie zeigen genau jene Mischung, die den Charakter des Party.San ausmacht: ein weitläufiges, trocken wirkendes Flugplatzgelände, schwarze Bandshirts und Kutten, dicht gedrängte Reihen vor der Bühne, Crowdsurfer, kreisende Moshpits und Besucher, die zwischen den Konzerten auf dem Gras sitzen. Tagsüber reichte das Licht von sonnig bis bewölkt, in der Nacht verwandelten farbige Strahler, dichter Bühnennebel und senkrechte Flammensäulen die große Bühne in eine massive Wand aus Licht und Feuer.</p>

<h2>Donnerstag: Todesblei, Grenzgänger und Abbath</h2>

<p>Der Donnerstag begann auf der Hauptbühne mit <strong>Bastard Grave</strong> und <strong>Broken Hope</strong> ohne lange Anlaufphase im Death Metal. Danach setzte <strong>Eternal Champion</strong> einen bewusst traditionellen Heavy-Metal-Kontrast, bevor <strong>Vltimas</strong> und <strong>Sadus</strong> den Schwerpunkt wieder in härtere Gefilde verschoben. Schon diese erste Hälfte zeigte die Stärke des Billings: Das Party.San musste sein Profil nicht aufgeben, um innerhalb dieses Profils unterschiedliche Farben zuzulassen.</p>

<p>Mit <strong>The Black Dahlia Murder</strong> zog das Tempo weiter an. <strong>Left To Die</strong> stellten anschließend die Verbindung zum klassischen Florida Death Metal her, während <strong>Darkened Nocturn Slaughtercult</strong> den Abend in Richtung kompromisslosen Black Metal kippen ließen. Der Schlussblock gehörte <strong>Terrorizer</strong> und <strong>Abbath</strong>: erst die grindige Verdichtung, dann der frostige, deutlich größer angelegte Tagesabschluss. Dramaturgisch war das ein starker Weg von der frühen Death-Metal-Schwere über Thrash und Black Metal bis zum Headlinerformat.</p>

<p>Die Tentstage hielt dagegen mit einer konzentrierten Folge aus Underground-Namen. <strong>Horresque</strong>, <strong>Wilt</strong>, <strong>Imha Tarikat</strong>, <strong>Rope Sect</strong>, <strong>Mephorash</strong>, <strong>Ritual Death</strong> und <strong>Schammasch</strong> spannten den Bogen von extremem Metal bis zu dunkler, ritualistischer und atmosphärischer Musik. Gerade diese Bühne verhinderte, dass der Donnerstag allein über seine großen Namen definiert wurde.</p>

<h2>Freitag: Von schwedischer Schwärze bis zur Behemoth-Großproduktion</h2>

<p>Am Freitag eröffnete <strong>Stillbirth</strong> die Mainstage, gefolgt von <strong>Obscurity</strong>, <strong>Enthroned</strong> und <strong>Afsky</strong>. Damit wechselten sich Death-Metal-Druck, Pagan-Färbung und unterschiedliche Schulen des Black Metal bereits am frühen Nachmittag ab. <strong>Sacramentum</strong> und <strong>Bewitched</strong> brachten anschließend schwedische Tradition und Black-Thrash-Schärfe ins Programm.</p>

<p>Die zweite Tageshälfte wurde noch massiver. <strong>Kraanium</strong> standen für brutale Direktheit, <strong>Incantation</strong> für jene finstere und schwere Ausprägung des Death Metal, die seit Jahrzehnten ihren eigenen Schatten wirft. <strong>Batushka</strong> verschoben die Wirkung danach in Richtung sakraler Inszenierung. Mit <strong>Sólstafir</strong> öffnete sich der Klangraum noch einmal deutlich, bevor <strong>Behemoth</strong> den Freitag beschlossen. Diese Abfolge war mehr als eine Reihe prominenter Namen: Sie führte von physischer Härte über rituelle Atmosphäre bis zur groß angelegten extremmetallischen Bühnenästhetik.</p>

<p>Im Zelt ging es parallel mit <strong>Cloak</strong>, <strong>Vorga</strong>, <strong>Los Males Del Mundo</strong>, <strong>Nervo Chaos</strong>, <strong>Varathron</strong>, <strong>Non Est Deus</strong> und <strong>Konvent</strong> weiter. Besonders der Wechsel vom griechischen Black-Metal-Urgestein Varathron zum modernen, schweren Doom von Konvent gab dem Abend zusätzliche Tiefe. Wer sich von der Monumentalität der Hauptbühne lösen wollte, fand hier das intimere und oft rauere Gegenprogramm.</p>

<h2>Samstag: Der stärkste Spannungsbogen des Wochenendes</h2>

<p>Die Tentstage startete am Samstag bereits am Vormittag mit <strong>Iron Walrus</strong> und <strong>Blood Fire Death</strong>. Auf der Hauptbühne folgten ab Mittag <strong>Ulthar</strong>, <strong>Regarde Les Hommes Tomber</strong>, <strong>Necrot</strong> und <strong>Ultha</strong>. Damit begann der letzte Tag nicht mit einem behutsamen Hochfahren, sondern mit einer dichten Folge aus Death und Black Metal, in der rohe Direktheit und atmosphärische Schwere nebeneinanderstanden.</p>

<p><strong>Hate</strong> hielten die extreme Linie, bevor <strong>Unto Others</strong> mit ihrer Mischung aus Heavy Metal, Gothic Rock und dunkler Melodik einen der auffälligsten Stilwechsel des Wochenendes setzten. Danach führten <strong>Sulphur Aeon</strong> und <strong>Obscura</strong> zurück zum Death Metal – einmal tief, finster und monumental, einmal technisch und präzise. <strong>Legion Of The Damned</strong> brachten Thrash-Schub in den frühen Abend.</p>

<p>Für das Finale erhöhte das Festival noch einmal den Druck. <strong>Anaal Nathrakh</strong> standen für maximal verdichtete Extremmusik, <strong>Paradise Lost</strong> ließen anschließend Doom, Melancholie und Gothic-Schwere auf die offene Landschaft treffen. Den letzten Auftritt des Festivals übernahmen <strong>Sodom</strong>. Nach drei Tagen voller Death- und Black-Metal-Dominanz war ihr Thrash-Metal-Schlusspunkt zugleich traditionsbewusst und unmittelbar – ein Abschluss ohne stilistische Verrenkung.</p>

<p>Auch die Tentstage blieb bis zum Abend stark besetzt. <strong>Malphas</strong>, <strong>Phantom Winter</strong>, <strong>Alkaloid</strong>, <strong>Disentomb</strong>, <strong>Heretoir</strong>, <strong>Hellripper</strong> und <strong>Akhlys</strong> deckten zwischen Black Metal, technischer Komplexität, brutaler Schwere und düsterer Atmosphäre ein breites Feld ab. Damit blieb das Zelt bis zum Ende mehr als eine Nebenbühne: Es war der Ort für die schärferen Kontraste und die tieferen Ausflüge in den Underground.</p>

<h2>Ein Festival mit klarer Haltung</h2>

<p>Das Party.San 2024 lebte nicht allein von den Headlinern Abbath, Behemoth und Sodom. Seine eigentliche Qualität lag in der Kombination aus großen Namen, historisch wichtigen Bands und konsequent kuratiertem Untergrund. Incantation, Sacramentum, Terrorizer, Varathron oder Paradise Lost brachten unterschiedliche Kapitel der Metal-Geschichte mit, während jüngere und kleinere Acts das Programm vor bloßer Rückschau bewahrten.</p>

<p>Auch visuell blieb der Jahrgang seiner Linie treu. Die offizielle Dokumentation zeigt keine künstlich überfrachtete Erlebniswelt, sondern Bühne, Flugplatz und Publikum als Mittelpunkt. Tagsüber dominierte das unmittelbare Konzertgeschehen; nachts sorgten Lichtfächer, Stroboskope, Nebel und Feuer für die großen Bilder. Die Kamera fand dabei ebenso die breite Masse vor der Mainstage wie einzelne Kutten, bemalte Gesichter, hochgereckte Fäuste und erschöpfte Besucher am Rand des Geländes.</p>

<h2>Fazit</h2>

<p>Das Party.San Open Air 2024 war ein Festivaljahrgang mit bemerkenswert sauberer Dramaturgie. Der Donnerstag führte von Old-School-Death und Heavy Metal über Black Metal bis zu Abbath. Der Freitag kombinierte Underground-Schärfe, Death-Metal-Tradition, atmosphärische Öffnung und Behemoth. Der Samstag setzte mit Unto Others, Anaal Nathrakh, Paradise Lost und Sodom die größten Kontraste.</p>

<p>So entstand ein Wochenende, das kompromisslos blieb, ohne eindimensional zu werden. Das Party.San bewies erneut, dass ein Extrem-Metal-Festival nicht möglichst viele Stilrichtungen bedienen muss. Es reicht, die eigenen Spielarten mit Sachkenntnis, Tiefe und Haltung zusammenzustellen – und genau das gelang 2024 auf dem Flugplatz Obermehler.</p>

<p><em>Faktenbasis dieses eigenständigen Rückblicks sind das offizielle <a href="https://www.party-san.de/history/2024" target="_blank" rel="noopener">Party.San-Archiv 2024</a>, die dort dokumentierten <a href="https://www.party-san.de/history/2024/bands-2024" target="_blank" rel="noopener">Bands</a>, die offizielle <a href="https://www.party-san.de/news/newsdetail/running-order-psoa-2024" target="_blank" rel="noopener">Running Order</a>, die <a href="https://www.party-san.de/history/2024/bilder-2024" target="_blank" rel="noopener">Festivalgalerie</a> sowie das <a href="https://www.youtube.com/watch?v=pQTQ4s7i7eo" target="_blank" rel="noopener">offizielle Aftermovie</a>. Der Text ist vollständig neu verfasst.</em></p>
HTML;

$sql = <<<'SQL'
INSERT INTO articles
(title, slug, content, excerpt, category_id, author, featured_image, status, created_at, updated_at)
VALUES (?, ?, ?, ?, ?, ?, ?, 'published', ?, NOW())
ON DUPLICATE KEY UPDATE
 title=VALUES(title), content=VALUES(content), excerpt=VALUES(excerpt),
 category_id=VALUES(category_id), author=VALUES(author), featured_image=VALUES(featured_image),
 status='published', created_at=VALUES(created_at), updated_at=NOW()
SQL;
$stmt = $pdo->prepare($sql);
$stmt->execute([
    $title,
    $slug,
    $content,
    $excerpt,
    $categoryId,
    'Redaktion',
    $image,
    '2024-08-11 18:00:00',
]);

$check = $pdo->prepare('SELECT id, title, slug, category_id, author, featured_image, status, created_at FROM articles WHERE slug = ?');
$check->execute([$slug]);
echo json_encode($check->fetch(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
