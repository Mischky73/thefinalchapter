<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

function fail(string $message): void {
    fwrite(STDERR, "FEHLER: {$message}\n");
    exit(1);
}

$category = getCategoryBySlug('festivals');
if (!$category) {
    fail('Die Kategorie festivals fehlt in der Testdatenbank.');
}

$started = microtime(true);
$articles = getArticlesByCategory((int) $category['id'], 33, 0);
$duration = microtime(true) - $started;

if ($duration > 0.25) {
    fail(sprintf(
        'Die Festival-Kategorieabfrage ist zu langsam (%.3f s statt maximal 0.250 s).',
        $duration
    ));
}

if (count($articles) > 33) {
    fail('Die Kategorieabfrage überschreitet das Seitenlimit.');
}

$allowedIds = getDB()->prepare('SELECT id FROM categories WHERE id = ? OR parent_id = ?');
$allowedIds->execute([(int) $category['id'], (int) $category['id']]);
$allowedIds = array_map('intval', $allowedIds->fetchAll(PDO::FETCH_COLUMN));

foreach ($articles as $article) {
    if (!in_array((int) $article['category_id'], $allowedIds, true)) {
        fail('Die Kategorieabfrage liefert einen Beitrag aus einer fremden Kategorie.');
    }
}

echo sprintf("OK: Festival-Kategorie in %.3f s geladen.\n", $duration);
