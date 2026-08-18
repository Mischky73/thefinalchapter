<?php
// Liest das erste <img> aus dem Content und setzt es als featured_image
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

// Alle Artikel ohne featured_image holen
$stmt = $db->query("SELECT id, content FROM articles WHERE (featured_image IS NULL OR featured_image = '') AND content != '' LIMIT 100000");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Verarbeite " . count($rows) . " Artikel...\n";

$updated = 0;
$skipped = 0;
$uploadDir = '/home/michael/projects/thefinalchapter/assets/img/uploads/';

$upd = $db->prepare("UPDATE articles SET featured_image = :img WHERE id = :id");

foreach ($rows as $row) {
    $content = $row['content'];

    // Erst: src mit lokalem Pfad (schon heruntergeladen)
    $localImg = '';

    // Suche nach wp-content/uploads URLs → prüfe ob lokal vorhanden
    if (preg_match_all('/src=["\']([^"\']*\/uploads\/[^"\']+)["\']/', $content, $matches)) {
        foreach ($matches[1] as $url) {
            $filename = basename(parse_url($url, PHP_URL_PATH));
            // Suche rekursiv in uploads/
            $found = glob($uploadDir . '*/' . $filename);
            if (empty($found)) {
                $found = glob($uploadDir . $filename);
            }
            if (!empty($found)) {
                // Relativer Pfad für DB
                $localImg = '/assets/img/uploads/' . basename(dirname($found[0])) . '/' . $filename;
                if (basename(dirname($found[0])) === 'uploads') {
                    $localImg = '/assets/img/uploads/' . $filename;
                }
                break;
            }
            // Datei nicht lokal, URL direkt nehmen
            if (empty($localImg)) {
                $localImg = $url;
            }
        }
    }

    // Fallback: erstes img src überhaupt
    if (empty($localImg)) {
        if (preg_match('/src=["\']([^"\']+\.(jpg|jpeg|png|webp|gif))["\']/', $content, $m)) {
            $localImg = $m[1];
        }
    }

    if (!empty($localImg)) {
        $upd->execute([':img' => $localImg, ':id' => $row['id']]);
        $updated++;
    } else {
        $skipped++;
    }

    if ($updated % 500 === 0 && $updated > 0) {
        echo "  $updated aktualisiert...\n";
        flush();
    }
}

echo "\nFertig!\n";
echo "  Aktualisiert: $updated\n";
echo "  Ohne Bild:    $skipped\n";
