<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

function expectBulk(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$db = getDB();
$categories = $db->query('SELECT id FROM categories ORDER BY id LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);
if (count($categories) < 2) {
    fwrite(STDERR, "FEHLER: Für den Test werden zwei Kategorien benötigt.\n");
    exit(1);
}

$sourceCategory = (int)$categories[0];
$targetCategory = (int)$categories[1];
$db->beginTransaction();

try {
    $stamp = bin2hex(random_bytes(5));
    $insert = $db->prepare("INSERT INTO articles (title, slug, content, excerpt, category_id, author, featured_image, status, created_at, updated_at) VALUES (?, ?, '', '', ?, 'Test', '', 'draft', NOW(), NOW())");
    $ids = [];
    foreach ([1, 2] as $number) {
        $insert->execute(["Bulk-Test {$number}", "bulk-test-{$stamp}-{$number}", $sourceCategory]);
        $ids[] = (int)$db->lastInsertId();
    }

    $strictIds = normalizeArticleIds([['x'], '12foo', '0013', '13', 13, '0', -4, 'abc', '', null]);
    expectBulk($strictIds === [13], 'Manipulierte oder nicht kanonische IDs müssen vollständig verworfen werden.');
    expectBulk(normalizeArticleStatus('published') === 'published', 'Status published muss erhalten bleiben.');
    expectBulk(normalizeArticleStatus('draft') === 'draft', 'Status draft muss erhalten bleiben.');
    expectBulk(normalizeArticleStatus('archived') === 'archived', 'Status archived muss erhalten bleiben.');
    expectBulk(normalizeArticleStatus('draft" onmouseover="alert(1)') === 'draft', 'Ungültiger Status muss sicher auf draft zurückfallen.');

    $moved = bulkMoveArticles([$ids[0], (string)$ids[1], 0, -1, $ids[0]], $targetCategory);
    expectBulk($moved === 2, "Verschieben muss genau zwei eindeutige Artikel melden, erhalten: {$moved}");

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $check = $db->prepare("SELECT COUNT(*) FROM articles WHERE id IN ({$placeholders}) AND category_id = ?");
    $check->execute([...$ids, $targetCategory]);
    expectBulk((int)$check->fetchColumn() === 2, 'Beide Artikel müssen in der Zielkategorie liegen.');

    $archived = archiveArticles([$ids[0], (string)$ids[1], 0, $ids[1]]);
    expectBulk($archived === 2, "Archivieren muss genau zwei eindeutige Artikel melden, erhalten: {$archived}");

    $deleted = bulkDeleteArticles([$ids[0], (string)$ids[1], 0, $ids[1]]);
    expectBulk($deleted === 2, "Löschen muss genau zwei eindeutige Artikel melden, erhalten: {$deleted}");

    $check = $db->prepare("SELECT COUNT(*) FROM articles WHERE id IN ({$placeholders})");
    $check->execute($ids);
    expectBulk((int)$check->fetchColumn() === 0, 'Beide ausgewählten Artikel müssen gelöscht sein.');

    $db->rollBack();
    echo "OK: Artikel können gesammelt verschoben und gelöscht werden.\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, 'FEHLER: ' . $e->getMessage() . "\n");
    exit(1);
}
