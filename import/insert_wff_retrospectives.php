<?php
declare(strict_types=1);

/**
 * Idempotenter lokaler Import der WFF-Retrospektiven 2003–2016 als Entwürfe.
 * Produktionsdaten werden nicht berührt.
 */
require_once __DIR__ . '/../includes/functions.php';

$bundlePath = __DIR__ . '/../research/wff/cms-import.json';
if (!is_file($bundlePath)) {
    throw new RuntimeException('Importbundle fehlt: ' . $bundlePath);
}

$payload = json_decode((string)file_get_contents($bundlePath), true, 512, JSON_THROW_ON_ERROR);
$articles = $payload['articles'] ?? null;
if (!is_array($articles) || count($articles) !== 14) {
    throw new RuntimeException('Importbundle muss genau 14 Artikel enthalten.');
}

$db = getDB();
$category = $db->prepare('SELECT id, name, slug FROM categories WHERE id = ? LIMIT 1');
$category->execute([53]);
$categoryRow = $category->fetch();
if (!$categoryRow || $categoryRow['slug'] !== 'wff' || $categoryRow['name'] !== 'With Full Force') {
    throw new RuntimeException('Zielkategorie 53/wff/With Full Force stimmt nicht.');
}

$lookup = $db->prepare('SELECT id, status FROM articles WHERE slug = ? LIMIT 1');
$insert = $db->prepare(
    'INSERT INTO articles
     (title, slug, content, excerpt, category_id, author, featured_image, status, created_at, updated_at)
     VALUES
     (:title, :slug, :content, :excerpt, 53, :author, :featured_image, \'draft\', :created_at, NOW())'
);
$update = $db->prepare(
    'UPDATE articles SET
       title = :title,
       content = :content,
       excerpt = :excerpt,
       category_id = 53,
       author = :author,
       featured_image = :featured_image,
       status = \'draft\',
       created_at = :created_at,
       updated_at = NOW()
     WHERE id = :id AND status = \'draft\''
);

$seenYears = [];
$inserted = 0;
$updated = 0;

$db->beginTransaction();
try {
    foreach ($articles as $article) {
        $year = (int)($article['year'] ?? 0);
        if ($year < 2003 || $year > 2016 || isset($seenYears[$year])) {
            throw new RuntimeException('Ungültiges oder doppeltes Jahr: ' . $year);
        }
        $seenYears[$year] = true;
        if ((int)($article['category_id'] ?? 0) !== 53 || ($article['status'] ?? '') !== 'draft') {
            throw new RuntimeException('Ungültige Kategorie oder Status für ' . $year);
        }

        $content = sanitizeArticleHtml((string)$article['content']);
        if (mb_strlen(trim(strip_tags($content))) < 1500) {
            throw new RuntimeException('Artikelinhalt zu kurz für ' . $year);
        }

        $values = [
            'title' => (string)$article['title'],
            'slug' => (string)$article['slug'],
            'content' => $content,
            'excerpt' => (string)$article['excerpt'],
            'author' => (string)$article['author'],
            'featured_image' => sanitizeLocalImagePath((string)$article['featured_image']),
            'created_at' => (string)$article['created_at'],
        ];

        $lookup->execute([$values['slug']]);
        $existing = $lookup->fetch();
        if ($existing) {
            if ($existing['status'] !== 'draft') {
                throw new RuntimeException('Bestehender Nicht-Entwurf darf nicht überschrieben werden: ' . $values['slug']);
            }
            $update->execute([
                'title' => $values['title'],
                'content' => $values['content'],
                'excerpt' => $values['excerpt'],
                'author' => $values['author'],
                'featured_image' => $values['featured_image'],
                'created_at' => $values['created_at'],
                'id' => (int)$existing['id'],
            ]);
            if ($update->rowCount() > 1) {
                throw new RuntimeException('Mehrfachupdate bei ' . $values['slug']);
            }
            $updated++;
        } else {
            $insert->execute($values);
            $inserted++;
        }
    }

    if (array_keys($seenYears) !== range(2003, 2016)) {
        throw new RuntimeException('Jahresfolge 2003–2016 ist unvollständig.');
    }
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    throw $error;
}

$slugs = array_column($articles, 'slug');
$placeholders = implode(',', array_fill(0, count($slugs), '?'));
$verify = $db->prepare(
    "SELECT id, title, slug, category_id, author, featured_image, status, created_at,
            CHAR_LENGTH(content) AS content_length
     FROM articles
     WHERE slug IN ($placeholders)
     ORDER BY created_at ASC, id ASC"
);
$verify->execute($slugs);
$rows = $verify->fetchAll();
if (count($rows) !== 14) {
    throw new RuntimeException('Rückprüfung ergab nicht 14 Datensätze.');
}
foreach ($rows as $row) {
    if ((int)$row['category_id'] !== 53 || $row['status'] !== 'draft' || (int)$row['content_length'] < 1500) {
        throw new RuntimeException('Rückprüfung fehlgeschlagen für ' . $row['slug']);
    }
}

echo json_encode([
    'scope' => 'LOCAL – production unchanged',
    'inserted' => $inserted,
    'updated' => $updated,
    'verified' => count($rows),
    'articles' => $rows,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
