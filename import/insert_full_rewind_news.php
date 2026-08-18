<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

$dataFile = __DIR__ . '/full_rewind_news_data.json';
$articles = json_decode(file_get_contents($dataFile), true, 512, JSON_THROW_ON_ERROR);

$pdo->beginTransaction();
try {
    $festivalNewsId = (int)$pdo->query("SELECT id FROM categories WHERE slug = 'festival-news' LIMIT 1")->fetchColumn();
    if (!$festivalNewsId) {
        throw new RuntimeException('Kategorie Festival-News wurde nicht gefunden.');
    }

    $stmt = $pdo->prepare("INSERT INTO categories (parent_id, name, slug, description) VALUES (?, 'Full Rewind', 'full-rewind', 'News und Meldungen zum Full Rewind Summer Open Air in Roitzschjora') ON DUPLICATE KEY UPDATE parent_id = VALUES(parent_id), name = VALUES(name), description = VALUES(description)");
    $stmt->execute([$festivalNewsId]);
    $categoryId = (int)$pdo->query("SELECT id FROM categories WHERE slug = 'full-rewind' LIMIT 1")->fetchColumn();

    $festivalNavId = (int)$pdo->query("SELECT id FROM nav_items WHERE label = 'Festivals' LIMIT 1")->fetchColumn();
    if ($festivalNavId) {
        $stmt = $pdo->prepare("INSERT INTO nav_items (parent_id, label, url, sort_order, target) SELECT ?, 'Full Rewind', '/category.php?slug=full-rewind', 15, '_self' WHERE NOT EXISTS (SELECT 1 FROM nav_items WHERE parent_id = ? AND label = 'Full Rewind')");
        $stmt->execute([$festivalNavId, $festivalNavId]);
    }

    $stmt = $pdo->prepare("UPDATE articles SET category_id = ? WHERE slug = 'full-rewind-summer-open-air-2025-alle-infos'");
    $stmt->execute([$categoryId]);

    // Die zunächst zusammengefasste Parkticket-Meldung wird in zwei eigenständige Produkteinträge getrennt.
    $pdo->exec("UPDATE articles SET slug = 'full-rewind-2026-pkw-parkticket' WHERE slug = 'full-rewind-2026-parktickets-fahrzeugregeln'");

    $sql = "INSERT INTO articles (title, slug, content, excerpt, category_id, author, featured_image, status, created_at, updated_at)
            VALUES (:title, :slug, :content, :excerpt, :category_id, 'Redaktion', :featured_image, 'published', :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), excerpt = VALUES(excerpt), category_id = VALUES(category_id), author = VALUES(author), featured_image = VALUES(featured_image), status = 'published', created_at = VALUES(created_at), updated_at = NOW()";
    $insert = $pdo->prepare($sql);
    foreach ($articles as $article) {
        $insert->execute([
            ':title' => $article['title'],
            ':slug' => $article['slug'],
            ':content' => $article['content'],
            ':excerpt' => $article['excerpt'],
            ':category_id' => $categoryId,
            ':featured_image' => $article['image'],
            ':created_at' => $article['date'],
            ':updated_at' => $article['date'],
        ]);
    }

    $pdo->commit();
    printf("Kategorie %d; %d Artikel veröffentlicht.\n", $categoryId, count($articles));
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}
