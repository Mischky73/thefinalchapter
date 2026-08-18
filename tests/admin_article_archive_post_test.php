<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_id('archive-post-test');
sessionStart();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'Archivtest';
$_SESSION['role'] = 'admin';

$db = getDB();
$db->beginTransaction();
$categoryId = (int)$db->query('SELECT id FROM categories ORDER BY id LIMIT 1')->fetchColumn();
$slug = 'archive-post-test-' . bin2hex(random_bytes(8));
$insert = $db->prepare(
    "INSERT INTO articles (title, slug, content, excerpt, category_id, author, featured_image, status, created_at)
     VALUES ('Archiv-POST-Test', ?, '', '', ?, 'Test', '', 'published', NOW())"
);
$insert->execute([$slug, $categoryId]);
$id = (int)$db->lastInsertId();

register_shutdown_function(static function () use ($db, $id): void {
    try {
        $read = $db->prepare('SELECT status, archived_from_status, archived_at FROM articles WHERE id = ?');
        $read->execute([$id]);
        $row = $read->fetch();
        $valid = $row
            && $row['status'] === 'archived'
            && $row['archived_from_status'] === 'published'
            && $row['archived_at'] !== null;
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if (!$valid) {
            fwrite(STDERR, "FEHLER: POST-Einzelarchivierung hat keinen konsistenten Archivzustand erzeugt.\n");
            exit(1);
        }
        echo "OK: Einzelarchivierung läuft ausschließlich per geprüftem POST.\n";
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        fwrite(STDERR, "FEHLER: {$error->getMessage()}\n");
    }
});

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'csrf_token' => csrfToken(),
    'single_archive' => (string)$id,
    'bulk_action' => '',
    'target_category' => '0',
    'confirm_delete' => 'no',
    'return_status' => '',
    'return_category' => '0',
];

include __DIR__ . '/../admin/articles.php';
