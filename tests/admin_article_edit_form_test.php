<?php
declare(strict_types=1);

$path = __DIR__ . '/../admin/article_edit.php';
$source = file_get_contents($path);
if ($source === false) {
    fwrite(STDERR, "article_edit.php konnte nicht gelesen werden.\n");
    exit(1);
}

$checks = [
    'bearbeitbares Erstellungsdatum' => 'type="datetime-local" id="created_at" name="created_at"',
    'created_at wird gespeichert' => "'created_at'",
    'lokale Bildpfade sind erlaubt' => 'type="text" id="featured_image" name="featured_image"',
    'Archivstatus wird beim Bearbeiten bewahrt' => '$isArchived',
    'Archivstatus wird nicht über das Statusfeld entfernt' => 'name="status" value="archived"',
];

foreach ($checks as $label => $needle) {
    if (!str_contains($source, $needle)) {
        fwrite(STDERR, "FEHLER: {$label} fehlt.\n");
        exit(1);
    }
}

if (str_contains($source, 'type="url" id="featured_image"')) {
    fwrite(STDERR, "FEHLER: Das Bildfeld erzwingt weiterhin eine vollständige URL.\n");
    exit(1);
}

echo "OK: Datumsfeld und lokale Bildpfade sind im Artikel-Editor unterstützt.\n";
