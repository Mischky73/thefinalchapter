<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

function expectInvariant(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function editablePayload(array $article, string $status): array {
    return [
        'title' => $article['title'],
        'slug' => $article['slug'],
        'content' => $article['content'],
        'excerpt' => $article['excerpt'],
        'category_id' => (int)$article['category_id'],
        'author' => $article['author'],
        'featured_image' => $article['featured_image'],
        'status' => $status,
        'created_at' => $article['created_at'],
    ];
}

$db = getDB();
$db->beginTransaction();
try {
    $categoryId = (int)$db->query('SELECT id FROM categories ORDER BY id LIMIT 1')->fetchColumn();
    $slug = 'archive-invariant-' . bin2hex(random_bytes(8));
    $insert = $db->prepare(
        "INSERT INTO articles (title, slug, content, excerpt, category_id, author, featured_image, status, created_at)
         VALUES ('Archiv-Invarianztest', ?, '', '', ?, 'Test', '', 'published', NOW())"
    );
    $insert->execute([$slug, $categoryId]);
    $id = (int)$db->lastInsertId();

    $staleActive = getArticleById($id);
    expectInvariant(archiveArticles([$id]) === 1, 'Testartikel konnte nicht archiviert werden.');
    saveArticle(editablePayload($staleActive, 'published'), $id);
    $afterStaleActiveSave = getArticleById($id);
    expectInvariant(
        $afterStaleActiveSave['status'] === 'archived'
        && $afterStaleActiveSave['archived_from_status'] === 'published'
        && $afterStaleActiveSave['archived_at'] !== null,
        'Ein veralteter aktiver Editor darf eine parallele Archivierung nicht überschreiben.'
    );

    $staleArchived = $afterStaleActiveSave;
    expectInvariant(restoreArchivedArticles([$id]) === 1, 'Testartikel konnte nicht wiederhergestellt werden.');
    saveArticle(editablePayload($staleArchived, 'archived'), $id);
    $afterStaleArchivedSave = getArticleById($id);
    expectInvariant(
        $afterStaleArchivedSave['status'] === 'published'
        && $afterStaleArchivedSave['archived_from_status'] === null
        && $afterStaleArchivedSave['archived_at'] === null,
        'Ein veralteter Archiveditor darf eine parallele Wiederherstellung nicht rückgängig machen.'
    );

    $directSlug = 'direct-archive-' . bin2hex(random_bytes(8));
    expectInvariant(saveArticle([
        'title' => 'Direkt archiviert',
        'slug' => $directSlug,
        'content' => '',
        'excerpt' => '',
        'category_id' => $categoryId,
        'author' => 'Test',
        'featured_image' => '',
        'status' => 'archived',
        'created_at' => date('Y-m-d H:i:s'),
    ]), 'Direkt angeforderter Archivstatus konnte nicht sicher gespeichert werden.');
    $findDirect = $db->prepare('SELECT * FROM articles WHERE slug = ?');
    $findDirect->execute([$directSlug]);
    $newArticle = $findDirect->fetch();
    expectInvariant(
        $newArticle['status'] === 'draft'
        && $newArticle['archived_from_status'] === null
        && $newArticle['archived_at'] === null,
        'Nur archiveArticles() darf einen Artikel in den Archivstatus versetzen.'
    );

    $newId = (int)$newArticle['id'];
    expectInvariant(archiveArticles([$newId]) === 1, 'Entwurf konnte nicht archiviert werden.');
    expectInvariant(restoreArchivedArticles([$newId]) === 1, 'Entwurf konnte nicht wiederhergestellt werden.');
    expectInvariant(getArticleById($newId)['status'] === 'draft', 'Ein archivierter Entwurf muss exakt als Entwurf wiederhergestellt werden.');

    $constraintRejected = false;
    try {
        $invalid = $db->prepare("UPDATE articles SET status = 'archived' WHERE id = ?");
        $invalid->execute([$id]);
    } catch (PDOException) {
        $constraintRejected = true;
    }
    expectInvariant($constraintRejected, 'Die Datenbank muss einen Archivstatus ohne Archivmetadaten ablehnen.');

    $db->rollBack();
    echo "OK: Archivstatus bleibt auch bei veralteten parallelen Editorständen konsistent.\n";
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "FEHLER: {$error->getMessage()}\n");
    exit(1);
}
