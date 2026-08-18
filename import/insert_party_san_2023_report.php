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

$title = 'Party.San Open Air 2023: Drei Tage zwischen Todesblei und schwarzer Magie';
$slug = 'party-san-open-air-2023-festivalbericht';
$excerpt = 'Vom 10. bis 12. August 2023 wurde der Flugplatz Obermehler zum Zentrum des extremen Metal: ein Rückblick auf drei Tage zwischen Death, Black, Thrash und Crust.';
$image = '/assets/img/uploads/party-san/party-san-open-air-2023-offizieller-flyer.png';
$content = <<<'HTML'
<p><strong>Vom 10. bis 12. August 2023 verwandelte sich der Flugplatz Obermehler bei Schlotheim erneut in einen Treffpunkt für die dunkleren und härteren Spielarten des Metal.</strong> Das Party.San Open Air setzte dabei nicht auf ein beliebig verbreitertes Festivalprogramm, sondern blieb seinem Kern treu: Death und Black Metal bestimmten das Bild, ergänzt um Thrash, Crust, Doom und wenige gezielt gesetzte stilistische Ausbrüche. Auf Hauptbühne und Sepulchral Voice Stage standen insgesamt 58 Bands – ein Programm, das weniger auf kurzfristige Trends als auf Konsequenz, Tradition und eine starke Underground-Basis zielte.</p>

<p>Schon die offiziellen Festivalbilder fassen die Atmosphäre treffend zusammen: eine dicht gefüllte Fläche vor der Bühne, weites Abendlicht über dem Flugplatz, später schwere Wolken, farbiger Bühnennebel und Flammensäulen vor der Hauptbühne. Das Party.San braucht keine überladene Kulisse. Die offene Landschaft, das konzentrierte Publikum und die kompromisslose Musikauswahl geben dem Festival seinen eigenen Charakter.</p>

<h2>Donnerstag: Death Metal übernimmt das Kommando</h2>

<p>Der erste Festivaltag begann mit <strong>Mentor</strong>, bevor <strong>Orbit Culture</strong>, <strong>Angelus Apatrida</strong>, <strong>Gatecreeper</strong> und <strong>Archspire</strong> unterschiedliche moderne Lesarten von Thrash und Death Metal auf die Hauptbühne brachten. Gerade diese frühe Abfolge zeigte die Spannweite des Billings: vom melodisch-groovenden Zugriff über knochentrockene Old-School-Schwere bis zur technischen Hochgeschwindigkeitsarbeit.</p>

<p>Mit <strong>Deströyer 666</strong> und <strong>Tribulation</strong> wurde der Ton dunkler und atmosphärischer, ehe der Abend vollständig dem klassischen und brutalen Death Metal gehörte. <strong>Nile</strong>, <strong>Deicide</strong> und <strong>Obituary</strong> bildeten einen Schlussblock, der wie eine kurze Geschichte des Genres wirkte: technische Monumentalität, blasphemische Direktheit und der unverwechselbar zähe Florida-Groove. Allein diese drei Namen hintereinander erklärten, warum das Party.San im europäischen Extrem-Metal-Kalender eine besondere Stellung besitzt.</p>

<p>Auf der Sepulchral Voice Stage lief parallel das tiefere Untergrundprogramm. <strong>Jade</strong>, <strong>Suborbital</strong>, <strong>Helslave</strong>, <strong>Balmog</strong>, <strong>Morbific</strong>, <strong>Graveyard</strong> und <strong>Postmortem</strong> hielten die stilistische Linie bewusst rau. Hier ging es nicht um große Gesten, sondern um Death und Black Metal in konzentrierter, unmittelbarer Form.</p>

<h2>Freitag: Zwischen Black Metal, Groove und kontrollierter Eskalation</h2>

<p>Der Freitag öffnete das Feld weiter. <strong>Be’lakor</strong> und <strong>Endseeker</strong> setzten melodische beziehungsweise norddeutsch geradlinige Death-Metal-Akzente. <strong>Kanonenfieber</strong>, <strong>Yoth Iria</strong> und <strong>Urgehal</strong> verschoben den Schwerpunkt anschließend in Richtung Black Metal, während <strong>Illdisposed</strong> ihre dänische Death-Metal-Tradition in das Tagesprogramm einbrachten.</p>

<p>Mit <strong>Midnight</strong> kam die schmutzige Black-Speed-Schlagseite hinzu, bevor <strong>Decapitated</strong> technische Präzision und <strong>Mantar</strong> die Wucht eines maximal verdichteten Duos gegenüberstellten. Das Finale bestritten <strong>Dying Fetus</strong> und <strong>Hypocrisy</strong>. Damit endete der Tag nicht mit bloßer Lautstärke, sondern mit zwei klar unterscheidbaren Formen extremer Musik: rhythmisch zermalmender Brutal Death Metal auf der einen und atmosphärisch geprägter schwedischer Death Metal auf der anderen Seite.</p>

<p>Auch die zweite Bühne blieb ihrem Namen verpflichtet. <strong>Spirit Possession</strong>, <strong>Horns Of Domination</strong>, <strong>Vircolac</strong>, <strong>Drowned</strong>, <strong>Concrete Winds</strong>, <strong>Black Curse</strong>, <strong>Sijjin</strong> und <strong>Grave Miasma</strong> bildeten eine nahezu lückenlose Kette aus finsterem Black und Death Metal. Wer das Party.San wegen seiner Underground-Verankerung besucht, fand hier einen der stärksten Programmblöcke des Wochenendes.</p>

<h2>Samstag: Historische Tiefe und ein besonderer Abschluss</h2>

<p>Am Samstag begann die Hauptbühne mit <strong>Atomwinter</strong> und <strong>Frozen Soul</strong> schwer und bodenständig. <strong>Spectral Wound</strong> und <strong>Ellende</strong> brachten unterschiedliche Black-Metal-Färbungen ein; dazwischen sorgten <strong>Skitsystem</strong> mit Crust-Punk-Schärfe und <strong>Skinless</strong> mit US-Death-Metal-Druck für harte Kontraste. <strong>Impiety</strong> verschärften das Tempo, bevor <strong>Immolation</strong> – kurzfristig für Vital Remains eingesprungen – einen der traditionsreichsten Namen des amerikanischen Death Metal ins Abendprogramm stellten.</p>

<p>Danach wurde die Dramaturgie breiter. <strong>Endstille</strong> hielten die Black-Metal-Kante, <strong>Borknagar</strong> öffneten den Klang mit progressiven und nordisch geprägten Elementen, und <strong>Kataklysm</strong> übernahmen den direkten Death-Metal-Part. Den Schlusspunkt setzten <strong>Enslaved</strong> mit einem angekündigten, exklusiven <em>Vikingligr Veldi</em>-Special-Set. Statt eines gewöhnlichen Headliner-Programms stand damit die frühe Bandgeschichte im Mittelpunkt – ein Abschluss, der nicht nur Größe, sondern auch historischen Tiefgang besaß.</p>

<p>Die Sepulchral Voice Stage setzte daneben noch einmal ein eigenes Ausrufezeichen. Von <strong>Chaos And Confusion</strong> über <strong>Tabula Rasa</strong>, <strong>The Night Eternal</strong>, <strong>Stormkeep</strong>, <strong>Arsgoatia</strong>, <strong>Wound</strong> und <strong>Heretic</strong> bis zu <strong>The Ruins Of Beverast</strong> reichte das Spektrum von jungem Heavy Metal über Black und Death Metal bis zu schwerer, atmosphärischer Finsternis.</p>

<h2>Fazit</h2>

<p>Das Party.San 2023 überzeugte vor allem durch seine klare Haltung. Große Namen wie Obituary, Hypocrisy, Kataklysm und Enslaved standen nicht isoliert über dem Programm, sondern waren in ein Billing eingebettet, das den Underground ernst nahm. Die Hauptbühne lieferte die großen Spannungsbögen, während die Sepulchral Voice Stage dem Wochenende Tiefe und Entdeckungswert gab.</p>

<p>Gerade diese Balance machte den Jahrgang stark: technische Perfektion traf auf rohe Direktheit, traditionsreicher Death Metal auf jungen Black Metal, internationale Schwergewichte auf kleine kompromisslose Bands. Das Ergebnis war kein weichgespültes Großereignis, sondern ein Festival mit erkennbarem Profil – konzentriert, dunkel und musikalisch konsequent.</p>

<p><em>Faktenbasis dieses eigenständigen Rückblicks sind das offizielle <a href="https://www.party-san.de/history/2023" target="_blank" rel="noopener">Party.San-Archiv 2023</a>, die dort dokumentierten <a href="https://www.party-san.de/history/2023/bands-2023" target="_blank" rel="noopener">Bands</a>, die offizielle <a href="https://www.party-san.de/history/2023/bilder-2023" target="_blank" rel="noopener">Festivalgalerie</a>, die veröffentlichte Running Order und das offizielle Aftermovie. Der Text ist vollständig neu verfasst.</em></p>
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
    '2023-08-13 18:00:00',
]);

$check = $pdo->prepare('SELECT id, title, slug, category_id, author, featured_image, status, created_at FROM articles WHERE slug = ?');
$check->execute([$slug]);
echo json_encode($check->fetch(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
