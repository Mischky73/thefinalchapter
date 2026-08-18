<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

function expectTour(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FEHLER: {$message}\n");
        exit(1);
    }
}

expectTour(function_exists('ensureTourEventsTable'), 'ensureTourEventsTable fehlt.');
expectTour(function_exists('getUpcomingTourEvents'), 'getUpcomingTourEvents fehlt.');
expectTour(function_exists('formatTourEventDate'), 'formatTourEventDate fehlt.');
expectTour(ensureTourEventsTable(), 'tour_events Tabelle kann nicht angelegt/geprüft werden.');

$db = getDB();
$stmt = $db->prepare('INSERT INTO tour_events (artist, event_title, venue, city, country, starts_at, ticket_url, source, source_event_id, status, last_seen_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, "test", ?, "published", NOW())
    ON DUPLICATE KEY UPDATE starts_at=VALUES(starts_at), status="published", last_seen_at=NOW()');
$stmt->execute(['Tour Test Band', 'Tour Test Band live', 'Testhalle', 'Teststadt', 'DE', '2099-01-02 20:00:00', 'https://example.com/tickets', 'tour-test-band-2099']);

$events = getUpcomingTourEvents(5, ['artist' => 'Tour Test Band']);
expectTour(count($events) >= 1, 'Test-Tourdaten werden nicht gefunden.');
expectTour($events[0]['artist'] === 'Tour Test Band', 'Artist-Filter liefert falschen Termin.');
expectTour(str_contains(formatTourEventDate('2099-01-02 20:00:00'), '02.01.2099'), 'Datumsformat ist falsch.');
expectTour(normalizeTourEventUrl('javascript:alert(1)') === '', 'Unsichere Ticket-URLs dürfen nicht durchgereicht werden.');

ob_start();
require __DIR__ . '/../includes/partials/sidebar.php';
$html = ob_get_clean();
expectTour(!str_contains($html, 'Tourkalender'), 'Tourkalender-Block soll nicht mehr in der rechten Sidebar erscheinen.');
expectTour(!str_contains($html, 'tourdaten.php'), 'Sidebar soll nicht mehr auf Tourdaten verlinken.');

foreach (['index.php', 'article.php', 'category.php', 'search.php', 'team.php', 'author.php', 'kontakt.php', 'impressum.php', 'datenschutz.php'] as $template) {
    $source = file_get_contents(__DIR__ . '/../' . $template);
    expectTour(!str_contains($source, '<aside class="left-sidebar"'), "{$template} darf keine linke Sidebar mehr rendern.");
}

$css = file_get_contents(__DIR__ . '/../assets/css/style.css');
expectTour(str_contains($css, '.no-left-sidebar'), 'CSS muss Layout ohne linke Sidebar unterstützen.');

echo "OK: Tourdaten sind aus der Sidebar entfernt und die linke Sidebar ist deaktiviert.\n";
