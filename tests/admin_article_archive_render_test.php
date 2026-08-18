<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

function expectArchiveUi(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function renderArticleAdmin(string $status): string {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = $status === '' ? [] : ['status' => $status];
    ob_start();
    include __DIR__ . '/../admin/articles.php';
    return (string)ob_get_clean();
}

session_id('archive-ui-test');
sessionStart();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'Archivtest';
$_SESSION['role'] = 'admin';

$db = getDB();
$db->beginTransaction();

try {
    $categoryId = (int)$db->query('SELECT id FROM categories ORDER BY id LIMIT 1')->fetchColumn();
    $title = 'Archiv-UI-Test ' . bin2hex(random_bytes(5));
    $slug = 'archive-ui-test-' . bin2hex(random_bytes(6));
    $stmt = $db->prepare(
        "INSERT INTO articles
         (title, slug, content, excerpt, category_id, author, featured_image, status, archived_from_status, archived_at, created_at)
         VALUES (?, ?, '', '', ?, 'Test', '', 'archived', 'published', NOW(), NOW())"
    );
    $stmt->execute([$title, $slug, $categoryId]);

    $activeHtml = renderArticleAdmin('');
    expectArchiveUi(!str_contains($activeHtml, $title), 'Archivierter Artikel erscheint in der aktiven Liste.');
    expectArchiveUi(str_contains($activeHtml, 'value="archive"'), 'Aktive Liste bietet Archivieren nicht an.');

    $archiveHtml = renderArticleAdmin('archived');
    expectArchiveUi(str_contains($archiveHtml, $title), 'Archivierter Artikel fehlt im Archivfilter.');
    expectArchiveUi(str_contains($archiveHtml, 'value="restore"'), 'Wiederherstellen fehlt im Archiv.');
    expectArchiveUi(str_contains($archiveHtml, 'value="delete"'), 'Endgültiges Löschen fehlt im Archiv.');
    expectArchiveUi(str_contains($archiveHtml, 'badge-archived'), 'Archivkennzeichnung fehlt.');
    expectArchiveUi(!str_contains($archiveHtml, 'value="archive"'), 'Archivansicht bietet erneutes Archivieren an.');

    $db->rollBack();
    echo "OK: Aktive Artikelliste und Archivansicht werden korrekt gerendert.\n";
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "FEHLER: {$error->getMessage()}\n");
    exit(1);
}
