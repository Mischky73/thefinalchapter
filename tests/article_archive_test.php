<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

function expectArchive(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$db = getDB();
$db->beginTransaction();

try {
    $categoryId = (int)$db->query('SELECT id FROM categories ORDER BY id LIMIT 1')->fetchColumn();
    expectArchive($categoryId > 0, 'Testkategorie fehlt.');

    $insert = $db->prepare(
        'INSERT INTO articles (title, slug, content, excerpt, category_id, author, featured_image, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );

    $ids = [];
    foreach (['published', 'draft', 'published'] as $index => $status) {
        $slug = 'archive-test-' . bin2hex(random_bytes(6));
        $insert->execute(["Archivtest {$index}", $slug, 'Test', 'Test', $categoryId, 'Test', '', $status]);
        $ids[] = (int)$db->lastInsertId();
    }

    expectArchive(archiveArticles([$ids[0], $ids[1]]) === 2, 'Zwei aktive Artikel müssen archiviert werden.');

    $read = $db->prepare('SELECT status, archived_from_status, archived_at FROM articles WHERE id = ?');
    $read->execute([$ids[0]]);
    $publishedArchive = $read->fetch();
    expectArchive($publishedArchive['status'] === 'archived', 'Veröffentlichter Artikel wurde nicht archiviert.');
    expectArchive($publishedArchive['archived_from_status'] === 'published', 'Ursprünglicher Veröffentlichungsstatus fehlt.');
    expectArchive(!empty($publishedArchive['archived_at']), 'Archivierungszeitpunkt fehlt.');

    $read->execute([$ids[1]]);
    $draftArchive = $read->fetch();
    expectArchive($draftArchive['status'] === 'archived', 'Entwurf wurde nicht archiviert.');
    expectArchive($draftArchive['archived_from_status'] === 'draft', 'Ursprünglicher Entwurfsstatus fehlt.');

    $archivedArticle = getArticleById($ids[0]);
    expectArchive(saveArticle([
        'title' => $archivedArticle['title'] . ' bearbeitet',
        'slug' => $archivedArticle['slug'],
        'content' => $archivedArticle['content'],
        'excerpt' => $archivedArticle['excerpt'],
        'category_id' => (int)$archivedArticle['category_id'],
        'author' => $archivedArticle['author'],
        'featured_image' => $archivedArticle['featured_image'],
        'status' => 'archived',
        'created_at' => $archivedArticle['created_at'],
    ], $ids[0]), 'Archivierter Artikel konnte nicht bearbeitet werden.');
    $read->execute([$ids[0]]);
    $afterEdit = $read->fetch();
    expectArchive($afterEdit['status'] === 'archived' && $afterEdit['archived_from_status'] === 'published', 'Bearbeiten darf einen Archivartikel nicht unbeabsichtigt wiederherstellen.');

    $activeIds = array_map('intval', array_column(getAllArticlesAdminFiltered('', 0), 'id'));
    expectArchive(!in_array($ids[0], $activeIds, true) && !in_array($ids[1], $activeIds, true), 'Archivierte Artikel erscheinen in der normalen Liste.');

    $archivedIds = array_map('intval', array_column(getAllArticlesAdminFiltered('archived', 0), 'id'));
    expectArchive(in_array($ids[0], $archivedIds, true) && in_array($ids[1], $archivedIds, true), 'Archivfilter findet archivierte Artikel nicht.');

    expectArchive(permanentlyDeleteArchivedArticles([$ids[2]]) === 0, 'Ein aktiver Artikel darf nicht endgültig gelöscht werden.');
    $exists = $db->prepare('SELECT COUNT(*) FROM articles WHERE id = ?');
    $exists->execute([$ids[2]]);
    expectArchive((int)$exists->fetchColumn() === 1, 'Aktiver Artikel wurde trotz Schutz gelöscht.');

    expectArchive(restoreArchivedArticles([$ids[0]]) === 1, 'Archivierter Artikel konnte nicht wiederhergestellt werden.');
    $read->execute([$ids[0]]);
    $restored = $read->fetch();
    expectArchive($restored['status'] === 'published', 'Artikel wurde nicht mit dem ursprünglichen Status wiederhergestellt.');
    expectArchive($restored['archived_from_status'] === null && $restored['archived_at'] === null, 'Archivmetadaten wurden beim Wiederherstellen nicht geleert.');

    expectArchive(permanentlyDeleteArchivedArticles([$ids[1]]) === 1, 'Archivierter Artikel wurde nicht endgültig gelöscht.');
    $exists->execute([$ids[1]]);
    expectArchive((int)$exists->fetchColumn() === 0, 'Endgültig gelöschter Archivartikel existiert noch.');

    $db->rollBack();
    echo "OK: Archivieren, Wiederherstellen und geschütztes endgültiges Löschen funktionieren.\n";
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "FEHLER: {$error->getMessage()}\n");
    exit(1);
}
