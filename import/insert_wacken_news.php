<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';

$pdo = getDB();
$dataFile = __DIR__ . '/wacken_articles_data.json';
if (!is_file($dataFile)) {
    fwrite(STDERR, "Datendatei fehlt: {$dataFile}\n");
    exit(1);
}
$data = json_decode((string) file_get_contents($dataFile), true, 512, JSON_THROW_ON_ERROR);
if (!is_array($data) || count($data) !== 229) {
    fwrite(STDERR, "Erwartet wurden 229 Datensätze.\n");
    exit(1);
}
$urls = array_column($data, 'source_url');
$slugs = array_column($data, 'slug');
if (count(array_unique($urls)) !== 229 || count(array_unique($slugs)) !== 229) {
    fwrite(STDERR, "Quell-URLs oder Slugs sind nicht eindeutig.\n");
    exit(1);
}

$pdo->beginTransaction();
try {
    $parentId = (int) $pdo->query("SELECT id FROM categories WHERE slug = 'festival-news' LIMIT 1")->fetchColumn();
    if ($parentId <= 0) {
        throw new RuntimeException('Kategorie Festival-News fehlt.');
    }
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = 'wacken-open-air' LIMIT 1");
    $stmt->execute();
    $categoryId = (int) $stmt->fetchColumn();
    if ($categoryId <= 0) {
        $stmt = $pdo->prepare("INSERT INTO categories (parent_id, name, slug, description) VALUES (?, 'Wacken Open Air', 'wacken-open-air', 'Offizielle Meldungen des Wacken Open Air')");
        $stmt->execute([$parentId]);
        $categoryId = (int) $pdo->lastInsertId();
    } else {
        $stmt = $pdo->prepare("UPDATE categories SET parent_id = ?, name = 'Wacken Open Air' WHERE id = ?");
        $stmt->execute([$parentId, $categoryId]);
    }

    $stmt = $pdo->prepare("SELECT id FROM nav_items WHERE url = '/category.php?slug=wacken-open-air' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetchColumn()) {
        $festivalMenuId = (int) $pdo->query("SELECT id FROM nav_items WHERE label = 'Festivals' AND parent_id IS NULL LIMIT 1")->fetchColumn();
        if ($festivalMenuId > 0) {
            $stmt = $pdo->prepare("INSERT INTO nav_items (parent_id, label, url, sort_order, target) VALUES (?, 'Wacken Open Air', '/category.php?slug=wacken-open-air', 18, '_self')");
            $stmt->execute([$festivalMenuId]);
        }
    }

    $sql = "INSERT INTO articles (title, slug, content, excerpt, category_id, author, featured_image, status, created_at, updated_at)
            VALUES (:title, :slug, :content, :excerpt, :category_id, 'Redaktion', :featured_image, 'published', :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE title=VALUES(title), content=VALUES(content), excerpt=VALUES(excerpt), category_id=VALUES(category_id), author='Redaktion', featured_image=VALUES(featured_image), status='published', created_at=VALUES(created_at), updated_at=NOW()";
    $insert = $pdo->prepare($sql);
    foreach ($data as $article) {
        $insert->execute([
            ':title' => $article['title'],
            ':slug' => $article['slug'],
            ':content' => $article['content'],
            ':excerpt' => $article['excerpt'],
            ':category_id' => $categoryId,
            ':featured_image' => $article['image'],
            ':created_at' => $article['date'] . ' 12:00:00',
            ':updated_at' => $article['date'] . ' 12:00:00',
        ]);
    }
    $pdo->commit();
    echo "Kategorie {$categoryId}; " . count($data) . " offizielle Wacken-Meldungen veröffentlicht.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
