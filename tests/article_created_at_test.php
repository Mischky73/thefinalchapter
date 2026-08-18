<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$categoryId = (int)$db->query('SELECT id FROM categories ORDER BY id LIMIT 1')->fetchColumn();
if ($categoryId < 1) {
    fwrite(STDERR, "Keine Testkategorie vorhanden.\n");
    exit(1);
}

$slug = 'created-at-test-' . bin2hex(random_bytes(6));
$createdAt = '2003-05-17 21:30:00';
$updatedCreatedAt = '2004-06-18 22:45:00';

$db->beginTransaction();
try {
    $data = [
        'title' => 'Erstellungsdatum Test',
        'slug' => $slug,
        'content' => '<p>Test</p>',
        'excerpt' => 'Test',
        'category_id' => $categoryId,
        'author' => 'Test',
        'featured_image' => '',
        'status' => 'draft',
        'created_at' => $createdAt,
    ];

    if (!saveArticle($data)) {
        throw new RuntimeException('Testartikel konnte nicht angelegt werden.');
    }

    $id = (int)$db->lastInsertId();
    $stored = $db->query("SELECT created_at FROM articles WHERE id = {$id}")->fetchColumn();
    if ($stored !== $createdAt) {
        throw new RuntimeException("INSERT speichert created_at nicht: erwartet {$createdAt}, erhalten {$stored}");
    }

    $data['created_at'] = $updatedCreatedAt;
    if (!saveArticle($data, $id)) {
        throw new RuntimeException('Testartikel konnte nicht aktualisiert werden.');
    }

    $stored = $db->query("SELECT created_at FROM articles WHERE id = {$id}")->fetchColumn();
    if ($stored !== $updatedCreatedAt) {
        throw new RuntimeException("UPDATE speichert created_at nicht: erwartet {$updatedCreatedAt}, erhalten {$stored}");
    }

    unset($data['created_at']);
    $data['title'] = 'Erstellungsdatum unverändert';
    if (!saveArticle($data, $id)) {
        throw new RuntimeException('Testartikel konnte ohne Datumsfeld nicht aktualisiert werden.');
    }

    $stored = $db->query("SELECT created_at FROM articles WHERE id = {$id}")->fetchColumn();
    if ($stored !== $updatedCreatedAt) {
        throw new RuntimeException("UPDATE ohne Datumsfeld verändert created_at: erwartet {$updatedCreatedAt}, erhalten {$stored}");
    }

    echo "OK: created_at wird gespeichert und ohne Datumsangabe beibehalten.\n";
    $db->rollBack();
    exit(0);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "FEHLER: {$e->getMessage()}\n");
    exit(1);
}
