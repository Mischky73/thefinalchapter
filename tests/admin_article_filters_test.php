<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

function expectTrue(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FEHLER: {$message}\n");
        exit(1);
    }
}

$db = getDB();
$db->beginTransaction();
$fixtureSlug = 'admin-filter-test-' . bin2hex(random_bytes(5));
$fixture = $db->prepare("INSERT INTO articles (title, slug, content, excerpt, category_id, author, featured_image, status, created_at, updated_at) VALUES ('Filter-Test', ?, '', '', 48, 'Test', '', 'draft', NOW(), NOW())");
$fixture->execute([$fixtureSlug]);

$allExpected = (int)$db->query("SELECT COUNT(*) FROM articles WHERE status != 'archived'")->fetchColumn();
$all = getAllArticlesAdminFiltered('', 0);
expectTrue(count($all) === $allExpected, 'Ohne Filter dürfen nur aktive Artikel geladen werden.');

$drafts = getAllArticlesAdminFiltered('draft', 0);
expectTrue($drafts !== [], 'Der Testbestand muss Entwürfe enthalten.');
expectTrue(count(array_filter($drafts, fn(array $a): bool => $a['status'] !== 'draft')) === 0,
    'Der Statusfilter Entwurf darf keine veröffentlichten Artikel liefern.');

$published = getAllArticlesAdminFiltered('published', 0);
expectTrue($published !== [], 'Der Testbestand muss veröffentlichte Artikel enthalten.');
expectTrue(count(array_filter($published, fn(array $a): bool => $a['status'] !== 'published')) === 0,
    'Der Statusfilter Veröffentlicht darf keine Entwürfe liefern.');

$categoryId = 48;
$categoryArticles = getAllArticlesAdminFiltered('', $categoryId);
expectTrue($categoryArticles !== [], 'Die Testkategorie In Flammen muss Artikel enthalten.');
expectTrue(count(array_filter($categoryArticles, fn(array $a): bool => (int)$a['category_id'] !== $categoryId)) === 0,
    'Der Kategorienfilter darf keine Artikel anderer Kategorien liefern.');

$combined = getAllArticlesAdminFiltered('draft', $categoryId);
expectTrue($combined !== [], 'Die kombinierte Auswahl muss In-Flammen-Entwürfe enthalten.');
expectTrue(count(array_filter($combined, fn(array $a): bool => $a['status'] !== 'draft' || (int)$a['category_id'] !== $categoryId)) === 0,
    'Status- und Kategorienfilter müssen gemeinsam wirken.');

$invalidStatus = getAllArticlesAdminFiltered('ungueltig', 0);
expectTrue(count($invalidStatus) === $allExpected, 'Ein ungültiger Status muss wie kein Statusfilter behandelt werden.');

$db->rollBack();
echo "OK: Artikel-Status- und Kategorienfilter funktionieren.\n";
