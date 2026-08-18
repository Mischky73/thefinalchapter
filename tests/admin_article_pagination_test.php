<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

function expectPagination(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FEHLER: {$message}\n");
        exit(1);
    }
}

$db = getDB();
$db->beginTransaction();

try {
    $categorySlug = 'admin-pagination-category-' . bin2hex(random_bytes(6));
    $category = $db->prepare(
        "INSERT INTO categories (parent_id, name, slug, description) VALUES (0, 'Pagination-Test', ?, '')"
    );
    $category->execute([$categorySlug]);
    $categoryId = (int)$db->lastInsertId();

    $insert = $db->prepare(
        "INSERT INTO articles
         (title, slug, content, excerpt, category_id, author, featured_image, status, created_at, updated_at)
         VALUES (?, ?, ?, '', ?, 'Pagination-Test', '', 'draft', ?, ?)"
    );

    $fixtureIds = [];
    for ($index = 0; $index < 51; $index++) {
        $title = 'Pagination Test ' . ($index + 1);
        $createdAt = sprintf('2099-01-01 12:00:%02d', $index);
        $slug = 'admin-pagination-test-' . $index . '-' . bin2hex(random_bytes(5));
        $insert->execute([$title, $slug, str_repeat('X', 10000), $categoryId, $createdAt, $createdAt]);
        $fixtureIds[] = (int)$db->lastInsertId();
    }

    $expectedDraftCount = (int)$db->query(
        "SELECT COUNT(*) FROM articles WHERE status = 'draft' AND category_id = {$categoryId}"
    )->fetchColumn();
    $actualDraftCount = countAdminArticlesFiltered('draft', $categoryId);
    expectPagination(
        $actualDraftCount === 51 && $actualDraftCount === $expectedDraftCount,
        'Die Gesamtzahl muss exakt zu Status- und Kategorienfilter passen.'
    );

    $firstPage = getAdminArticlesPage('draft', $categoryId, 50, 0);
    expectPagination(count($firstPage) === 50, 'Die erste Seite muss exakt 50 von 51 Artikeln enthalten.');
    expectPagination(
        array_map('intval', array_column($firstPage, 'id')) === array_reverse(array_slice($fixtureIds, 1)),
        'Die erste Seite muss die neuesten Artikel in stabiler Reihenfolge liefern.'
    );
    expectPagination(
        !array_key_exists('content', $firstPage[0]) && !array_key_exists('excerpt', $firstPage[0]),
        'Die Listenabfrage darf umfangreiche Artikeltexte und Auszüge nicht laden.'
    );

    $secondPage = getAdminArticlesPage('draft', $categoryId, 50, 50);
    expectPagination(count($secondPage) === 1, 'Die zweite Seite muss den letzten von 51 Artikeln enthalten.');
    expectPagination(
        (int)$secondPage[0]['id'] === $fixtureIds[0],
        'LIMIT und OFFSET müssen ohne Überschneidung zur nächsten Seite führen.'
    );

    $invalidStatusCount = countAdminArticlesFiltered('ungueltig', 0);
    $expectedActiveCount = (int)$db->query("SELECT COUNT(*) FROM articles WHERE status != 'archived'")->fetchColumn();
    expectPagination(
        $invalidStatusCount === $expectedActiveCount,
        'Ein ungültiger Status muss wie der Standardfilter für aktive Artikel behandelt werden.'
    );

    expectPagination(
        buildAdminArticlePageUrl(3, 'draft', $categoryId) ===
            'articles.php?status=draft&category=' . $categoryId . '&page=3',
        'Seitenlinks müssen Status, Kategorie und Seitennummer erhalten.'
    );
    expectPagination(
        buildAdminArticlePageUrl(1, 'ungueltig', 0) === 'articles.php',
        'Ungültige Filter und Seite 1 dürfen nicht in den Seitenlink übernommen werden.'
    );

    expectPagination(normalizeNonNegativeIntInput('12') === 12,
        'Eine gültige positive Ganzzahl muss übernommen werden.');
    expectPagination(normalizeNonNegativeIntInput('-4') === 0,
        'Negative Eingaben müssen auf null begrenzt werden.');
    expectPagination(normalizeNonNegativeIntInput(['12'], 7) === 7,
        'Array-Eingaben müssen ohne Warnung auf den Standardwert zurückfallen.');
    expectPagination(normalizeNonNegativeIntInput(null, 3) === 3,
        'Nicht skalare oder fehlende Eingaben müssen den Standardwert verwenden.');

    $db->rollBack();
    echo "OK: Admin-Artikelpagination, Filter, Gesamtzahl und schlanke Auswahl funktionieren.\n";
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    throw $error;
}
