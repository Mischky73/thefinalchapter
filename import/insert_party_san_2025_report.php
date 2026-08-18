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

$title = 'Party.San Open Air 2025: Grindcore-Sturm und schwarzer Triumph';
$slug = 'party-san-open-air-2025-festivalbericht';
$excerpt = 'Vom 7. bis 9. August 2025 wurde der Flugplatz Obermehler erneut zum Todesacker: 57 Auftritte zwischen Death, Black, Grindcore, Doom und Thrash Metal.';
$image = '/assets/img/uploads/party-san/party-san-open-air-2025-offizielle-running-order.jpg';
$content = <<<'HTML'
<p><strong>Vom 7. bis 9. August 2025 regierte auf dem Flugplatz Obermehler bei Schlotheim erneut der extreme Metal.</strong> Das Party.San Open Air stellte auf Mainstage und Tentstage eine finale Running Order mit 57 Auftritten zusammen. Death und Black Metal blieben das Rückgrat, doch Grindcore, Thrash, Doom, Gothic und traditioneller Heavy Metal sorgten für die entscheidenden Kontraste. Das Ergebnis war kein beliebiges Sammelsurium, sondern ein Wochenende mit klarer Handschrift und erstaunlich breiter Dramaturgie.</p>

<p>Das offizielle Aftermovie zeigt die vertraute Party.San-Welt in komprimierter Form: ein dichtes Publikum vor der offenen Hauptbühne, Metal-Horns und Fäuste über den Köpfen, Headbanging im Gegenlicht und Musiker unmittelbar an der Bühnenkante. Helles Tageslicht wechselt mit rotem, grünem und blauem Bühnenlicht, harte Silhouetten schneiden sich durch Nebel und Rauch. Zwischen Corpsepaint, Kutten und schwarzen Shirts finden sich ebenso ruhige Begegnungen auf dem Gelände – der Todesacker als Treffpunkt, nicht nur als Konzertfläche.</p>

<h2>Donnerstag: Vom Untergrund bis zum Thrash-Finale</h2>

<p>Der Donnerstag startete auf der Mainstage ohne Umweg. <strong>Rotpit</strong> und <strong>Extermination Dismemberment</strong> eröffneten mit schwerem Death Metal und Slam, bevor <strong>… And Oceans</strong> und <strong>The Spirit</strong> den Klang in Richtung symphonischer beziehungsweise melodisch geschärfter Black-Death-Sphären verschoben. Schon die ersten vier Bands machten deutlich, wie das Party.San 2025 seine Härte organisierte: nicht als monotone Wand, sondern als Wechsel unterschiedlicher extremer Schulen.</p>

<p>Mit <strong>Dool</strong> wurde das Programm offener und atmosphärischer. <strong>Grand Magus</strong> setzten anschließend klassischen Heavy-Metal-Nachdruck, ehe <strong>Fleshgod Apocalypse</strong> die Bühne mit orchestraler Death-Metal-Wucht wieder maximal verdichteten. <strong>Harakiri For The Sky</strong> brachten Melancholie und lange Spannungsbögen in den Abend. Danach übernahmen zwei Veteranen: <strong>Napalm Death</strong> standen für die radikale Kürze und politische Schärfe des Grindcore, <strong>Dark Angel</strong> beschlossen den Tag mit traditionsreichem US-Thrash.</p>

<p>Die Tentstage hielt mit <strong>Servant</strong>, <strong>Outlaw</strong>, <strong>Theotoxin</strong>, <strong>Firtan</strong>, <strong>Karg</strong>, <strong>Chaos Invocation</strong> und <strong>Agrypnie</strong> einen nahezu durchgehenden Black-Metal-Faden. Die Schattierungen reichten von direkter Aggression bis zu atmosphärischer und postmetallischer Weite. Während die Mainstage ihre Stilwechsel offen ausspielte, wirkte das Zelt wie ein konzentriertes Gegenprogramm für die dunklere Seite des Billings.</p>

<h2>Freitag: Extreme Vielfalt und ein historischer Schlusspunkt</h2>

<p>Der Freitag war der stilistisch beweglichste Tag. <strong>Party Cannon</strong> und <strong>Hyperdontia</strong> begannen mit Slam und Death Metal, bevor <strong>The Vision Bleak</strong> ihre theatralische Gothic-Metal-Atmosphäre einbrachten. <strong>Crypt Sermon</strong> verschoben den Schwerpunkt zum epischen Doom, <strong>Wayfarer</strong> verbanden Black Metal mit weiter, staubiger Atmosphäre. Diese frühe Abfolge war für Party.San-Verhältnisse ungewöhnlich farbig, ohne den dunklen Grundton zu verlassen.</p>

<p><strong>Hellbutcher</strong> zogen die Schraube mit Black Thrash wieder an, <strong>Defleshed</strong> und <strong>Suffocation</strong> führten danach tief in die Death-Metal-Tradition. Mit <strong>Brujeria</strong> kam der schmutzige Grindcore-Angriff, ehe <strong>Rotting Christ</strong> ihre markante griechische Black-Metal-Handschrift in den Abend trugen. <strong>I Am Morbid</strong> hielten anschließend die Verbindung zur klassischen Florida-Schule.</p>

<p>Den Freitag beendeten <strong>Triptykon plays Celtic Frost</strong>. Schon die Ankündigung machte deutlich, dass hier nicht einfach ein reguläres Headlinerprogramm vorgesehen war, sondern ein besonderer Blick auf das Erbe von Celtic Frost. Nach einem Tag zwischen Slam, Gothic, Doom, Death, Grindcore und Black Metal war dieser schwere, historische Schlussblock die logische Klammer: extrem, einflussreich und weit über enge Genregrenzen hinaus wirksam.</p>

<p>Im Zelt standen <strong>Heretic Warfare</strong>, <strong>Naxen</strong>, <strong>Mass Worship</strong>, <strong>Friisk</strong>, <strong>Gutslit</strong>, <strong>Drudensang</strong> und <strong>Imperial Triumphant</strong>. Der Abschluss durch Imperial Triumphant setzte einen besonders eigenwilligen Kontrast. Ihre dissonante, avantgardistische Ausrichtung stand weit entfernt von geradliniger Festivalroutine und gab dem zweiten Tag einen bewusst unbequemen Nebenschauplatz.</p>

<h2>Samstag: Death Metal übernimmt den Todesacker</h2>

<p>Am Samstag begann die Tentstage bereits am Vormittag mit <strong>Ass Cobra</strong> und <strong>Macbeth</strong>. Auf der Hauptbühne eröffneten <strong>Scalpture</strong>, gefolgt von den Grindcore-Veteranen <strong>Blockheads</strong>. <strong>Necrowretch</strong>, <strong>Schizophrenia</strong> und <strong>Analepsy</strong> hielten den Tag anschließend fest zwischen Blackened Death, Thrash und brutaler Death-Metal-Schwere.</p>

<p>Mit <strong>Ereb Altor</strong> öffnete sich das Programm zu epischeren und nordisch geprägten Klängen. <strong>Skeletal Remains</strong> zogen die Linie zurück zum klassischen Death Metal, bevor <strong>Pig Destroyer</strong> den frühen Abend in einen kompakten Grindcore-Angriff verwandelten. Danach begann der große Schlussbogen: <strong>Grave</strong> standen für schwedische Death-Metal-Tradition, <strong>Tiamat</strong> für dunkle Atmosphäre und die Öffnung in Richtung Gothic.</p>

<p><strong>Gorgoroth</strong> verschärften die Nacht noch einmal mit norwegischem Black Metal. Den letzten Auftritt des Festivals übernahmen <strong>Bloodbath</strong>. Damit endete das Party.San 2025 dort, wo sein musikalisches Zentrum liegt: beim Death Metal – nicht als nostalgische Pflichtübung, sondern als konsequenter Abschluss eines Tages, der zahlreiche Generationen und Spielarten des Genres zusammengeführt hatte.</p>

<p>Die Tentstage blieb auch am Samstag mehr als ein Lückenfüller. <strong>Nightbearer</strong>, <strong>Avulsed</strong>, <strong>Night In Gales</strong>, <strong>Dödsrit</strong>, <strong>MØL</strong>, <strong>Kvaen</strong> und <strong>Fulci</strong> führten vom klassischen und melodischen Death Metal über Crust- und Post-Black-Einflüsse bis zu finsterem Death-Metal-Kino. Besonders die Abfolge Dödsrit, MØL und Kvaen gab dem Zelt am Abend eine eigenständige, atmosphärisch aufgeladene Dramaturgie.</p>

<h2>Mehr als die Summe der Headliner</h2>

<p>Das Party.San 2025 lebte nicht allein von Dark Angel, Triptykon, Gorgoroth und Bloodbath. Die eigentliche Stärke steckte in den Übergängen: Dool vor Grand Magus, Harakiri For The Sky vor Napalm Death, The Vision Bleak zwischen Slam und Doom oder Tiamat zwischen Grave und Gorgoroth. Solche Wechsel verhindern Gleichförmigkeit und zeigen zugleich, wie weit sich ein konsequent kuratiertes Extrem-Metal-Festival öffnen kann.</p>

<p>Auch die zweite Bühne erfüllte eine klare Funktion. Sie bot nicht einfach kleinere Varianten des Mainstage-Programms, sondern eigene Linien: am Donnerstag viel Black Metal, am Freitag dissonante und undergroundnahe Extreme, am Samstag eine Mischung aus Death Metal und atmosphärischer Schwärze. Dadurch konnten beide Bühnen unterschiedliche Geschichten erzählen, ohne das gemeinsame Profil zu verlieren.</p>

<h2>Fazit</h2>

<p>Der Jahrgang 2025 verband historische Namen mit einem starken Unterbau. Napalm Death, Dark Angel, Suffocation, Rotting Christ, Triptykon, Grave, Tiamat, Gorgoroth und Bloodbath standen in einem Programm, das jüngeren Bands und sperrigeren Positionen ebenso Raum gab. Die offizielle Dokumentation zeigt dazu jene Bilder, die das Party.San seit Jahren prägen: Nähe zwischen Bühne und Publikum, konzentrierte Dunkelheit, harte Lichtwechsel und eine Gemeinschaft, die den extremen Metal nicht als Kulisse, sondern als gemeinsame Sprache versteht.</p>

<p>So blieb das Festival kompromisslos, ohne vorhersehbar zu werden. Der Donnerstag setzte auf Kontraste, der Freitag auf historische und stilistische Tiefe, der Samstag auf einen massiven Death-Metal-Schlussbogen. Das Party.San Open Air 2025 bestätigte damit erneut seine Sonderstellung: kein weichgespültes Großereignis, sondern ein Festival mit Haltung, Gedächtnis und einem festen Platz für den Untergrund.</p>

<p><em>Faktenbasis dieses eigenständigen Rückblicks sind das offizielle <a href="https://www.party-san.de/history/2025" target="_blank" rel="noopener">Party.San-Archiv 2025</a>, die dort dokumentierten <a href="https://www.party-san.de/history/bands-2025" target="_blank" rel="noopener">Bands</a>, die finale <a href="https://www.party-san.de/news/newsdetail/running-order-2025" target="_blank" rel="noopener">Running Order</a>, die offizielle <a href="https://www.party-san.de/news/newsdetail/tickets-partysan-open-air-2025" target="_blank" rel="noopener">Ticketinformation</a> sowie das <a href="https://www.youtube.com/watch?v=OLBmZD6grqI" target="_blank" rel="noopener">offizielle Aftermovie</a>. Der Text ist vollständig neu verfasst.</em></p>
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
    '2025-08-10 18:00:00',
]);

$check = $pdo->prepare('SELECT id, title, slug, category_id, author, featured_image, status, created_at FROM articles WHERE slug = ?');
$check->execute([$slug]);
echo json_encode($check->fetch(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
