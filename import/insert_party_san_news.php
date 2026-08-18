<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = getDB();
$category = $db->prepare("SELECT id FROM categories WHERE slug = ? LIMIT 1");
$category->execute(['party-san']);
$categoryId = (int)$category->fetchColumn();
if (!$categoryId) {
    throw new RuntimeException('Unterkategorie Party San nicht gefunden.');
}

$dataFile = __DIR__ . '/party_san_news_data.json';
$news = json_decode((string)file_get_contents($dataFile), true, 512, JSON_THROW_ON_ERROR);
if (count($news) !== 36) {
    throw new RuntimeException('Unerwartete Anzahl Party-San-Meldungen: ' . count($news));
}

$sql = 'INSERT INTO articles
        (title, slug, content, excerpt, category_id, author, featured_image, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, "draft", ?, NOW())
        ON DUPLICATE KEY UPDATE
          title=VALUES(title), content=VALUES(content), excerpt=VALUES(excerpt),
          category_id=VALUES(category_id), author=VALUES(author),
          featured_image=VALUES(featured_image), created_at=VALUES(created_at), updated_at=NOW()';
$stmt = $db->prepare($sql);

$db->beginTransaction();
try {
    foreach ($news as $item) {
        $stmt->execute([
            $item['title'], $item['slug'], $item['content'], $item['excerpt'],
            $categoryId, 'Redaktion', $item['image'] ?: null, $item['date'],
        ]);
    }
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}

$count = $db->prepare('SELECT COUNT(*) FROM articles WHERE category_id=? AND slug LIKE "party-san-news-%"');
$count->execute([$categoryId]);
printf("Party-San-News gespeichert: %d\n", (int)$count->fetchColumn());
