<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

function expectSidebar(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FEHLER: {$message}\n");
        exit(1);
    }
}

expectSidebar(function_exists('getFestivalNewsSidebarGroups'),
    'Die gruppierte Festival-News-Abfrage fehlt.');
expectSidebar(function_exists('getFestivalSidebarMetadata'),
    'Die Festivaltermine und Logos fehlen.');
expectSidebar(function_exists('getFestivalCountdown'),
    'Die Festival-Countdown-Funktion fehlt.');
expectSidebar(function_exists('isFestivalArticleNew'),
    'Die 48-Stunden-Markierung fehlt.');

$sidebarNow = new DateTimeImmutable('2026-07-18 01:00:00', new DateTimeZone('Europe/Berlin'));
$festivalGroups = getFestivalNewsSidebarGroups(5, $sidebarNow);
expectSidebar(count($festivalGroups) >= 2,
    'Mindestens Party San und Wacken müssen als Festival-News-Gruppen vorhanden sein.');
expectSidebar(($festivalGroups[0]['slug'] ?? '') === 'party-san-news',
    'Party San muss als erste Festival-News-Gruppe erscheinen.');
expectSidebar(($festivalGroups[1]['slug'] ?? '') === 'wacken-news',
    'Wacken muss als zweite Festival-News-Gruppe erscheinen.');
expectSidebar(array_column($festivalGroups, 'slug') === [
    'party-san-news',
    'wacken-news',
    'full-rewind-news',
    'summer-breeze-news',
    'in-flammen-news',
    'rockharz-news',
], 'Nach Party San und Wacken müssen kommende Festivals zuerst und vergangene danach erscheinen.');

foreach ($festivalGroups as $festivalGroup) {
    expectSidebar(!empty($festivalGroup['logo']),
        'Jede Festivalgruppe benötigt ein Logo.');
    expectSidebar(is_file(__DIR__ . '/..' . $festivalGroup['logo']),
        'Jedes Festival-Logo muss lokal vorhanden sein.');
    expectSidebar(!empty($festivalGroup['date_label']) && !empty($festivalGroup['countdown_label']),
        'Jede Festivalgruppe benötigt Termin und Countdown-Status.');
}
expectSidebar(($festivalGroups[0]['date_label'] ?? '') === '06.–08.08.2026',
    'Der Party-San-Termin muss korrekt formatiert sein.');
expectSidebar(($festivalGroups[0]['countdown_label'] ?? '') === 'Noch 19 Tage',
    'Der Party-San-Countdown muss am Testdatum 19 Tage anzeigen.');
expectSidebar(isFestivalArticleNew('2026-07-17 02:00:00', $sidebarNow),
    'Eine Meldung innerhalb von 48 Stunden muss als neu gelten.');
expectSidebar(!isFestivalArticleNew('2026-07-16 00:59:59', $sidebarNow),
    'Eine ältere Meldung darf nicht als neu gelten.');

$expectedFestivalSlugs = getDB()->query(
    "SELECT c.slug
     FROM categories c
     JOIN categories p ON p.id = c.parent_id
     WHERE p.slug = 'festival-news'
       AND c.slug <> 'wff-news'
     ORDER BY c.slug"
)->fetchAll(PDO::FETCH_COLUMN);
$actualFestivalSlugs = array_column($festivalGroups, 'slug');
$sortedActualFestivalSlugs = $actualFestivalSlugs;
sort($sortedActualFestivalSlugs);
expectSidebar($sortedActualFestivalSlugs === $expectedFestivalSlugs,
    'Alle Festival-News-Unterkategorien müssen genau einmal ausgegeben werden.');

foreach ($festivalGroups as $group) {
    expectSidebar(count($group['articles']) <= 5,
        'Jede Festivalgruppe darf höchstens fünf Meldungen enthalten.');
    $dates = array_column($group['articles'], 'created_at');
    $sortedDates = $dates;
    rsort($sortedDates);
    expectSidebar($dates === $sortedDates,
        'Die Meldungen jeder Festivalgruppe müssen nach Datum absteigend sortiert sein.');
    foreach ($group['articles'] as $article) {
        expectSidebar(($article['category_slug'] ?? '') === $group['slug'],
            'Jede Meldung muss zur angezeigten Festival-Unterkategorie gehören.');
    }
}

$cats = [[
    'name' => 'Testkategorie darf nicht erscheinen',
    'slug' => 'testkategorie-sidebar',
    'article_count' => 123,
]];
$activeCatSlug = 'testkategorie-sidebar';
$latestArticles = [[
    'title' => 'Testartikel darf nicht erscheinen',
    'slug' => 'testartikel-sidebar',
    'featured_image' => '',
    'created_at' => '2026-07-18 12:00:00',
]];

$festivalNewsNow = $sidebarNow;
ob_start();
require __DIR__ . '/../includes/partials/sidebar.php';
$html = ob_get_clean();

expectSidebar(substr_count($html, '<details class="festival-news-group"') === count($festivalGroups),
    'Die Sidebar muss jede Festival-Unterkategorie als aufklappbare Gruppe rendern.');
expectSidebar((bool)preg_match('/<details class="festival-news-group" data-festival-slug="party-san-news" open>/', $html),
    'Party San muss standardmäßig geöffnet sein.');
expectSidebar((bool)preg_match('/<details class="festival-news-group" data-festival-slug="wacken-news" open>/', $html),
    'Wacken muss standardmäßig geöffnet sein.');
foreach (array_slice($festivalGroups, 2) as $closedGroup) {
    $openingTag = '<details class="festival-news-group" data-festival-slug="' . h($closedGroup['slug']) . '">';
    expectSidebar(str_contains($html, $openingTag),
        'Alle weiteren Festivalgruppen müssen standardmäßig geschlossen sein.');
}
expectSidebar(substr_count($html, 'class="festival-news-all-link"') === count($festivalGroups),
    'Jede Festivalgruppe benötigt einen eigenen „Alle News“-Link.');
expectSidebar(substr_count($html, 'class="festival-news-logo"') === count($festivalGroups),
    'Jede Festivalgruppe muss ihr Logo anzeigen.');
expectSidebar(substr_count($html, 'class="festival-news-date"') === count($festivalGroups),
    'Jede Festivalgruppe muss Termin und Countdown anzeigen.');
expectSidebar(str_contains($html, 'class="festival-news-new"'),
    'Aktuelle Meldungen müssen mit „NEU“ gekennzeichnet werden.');
$partySanPosition = strpos($html, 'data-festival-slug="party-san-news"');
$wackenPosition = strpos($html, 'data-festival-slug="wacken-news"');
expectSidebar($partySanPosition !== false && $wackenPosition !== false && $partySanPosition < $wackenPosition,
    'Party San muss in der gerenderten Sidebar vor Wacken stehen.');

expectSidebar(!str_contains($html, '>Kategorien</h3>'),
    'Die öffentliche Sidebar darf keine Überschrift „Kategorien“ mehr enthalten.');
expectSidebar(!str_contains($html, 'class="category-list"'),
    'Die öffentliche Sidebar darf keine Kategorienliste mehr rendern.');
expectSidebar(!str_contains($html, 'Testkategorie darf nicht erscheinen'),
    'Übergebene Kategorien dürfen in der öffentlichen Sidebar nicht erscheinen.');
expectSidebar(!str_contains($html, 'Zuletzt erschienen'),
    'Die öffentliche Sidebar darf den Block „Zuletzt erschienen“ nicht mehr enthalten.');
expectSidebar(!str_contains($html, 'Testartikel darf nicht erscheinen'),
    'Übergebene neueste Artikel dürfen in der öffentlichen Sidebar nicht erscheinen.');
expectSidebar(str_contains($html, 'Festival-News'),
    'Festival-News muss in der Sidebar erhalten bleiben.');
expectSidebar(!str_contains($html, 'Über uns'),
    'Der Bereich „Über uns“ darf nicht mehr in der Sidebar erscheinen.');
expectSidebar(!str_contains($html, 'Social Media'),
    'Der Bereich „Social Media“ darf nicht mehr in der Sidebar erscheinen.');

fwrite(STDOUT, "OK: Nur Festival-News bleibt als öffentlicher Sidebar-Inhalt erhalten.\n");
