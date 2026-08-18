<?php
declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/../admin/articles.php');
if ($source === false) {
    fwrite(STDERR, "FEHLER: admin/articles.php konnte nicht gelesen werden.\n");
    exit(1);
}

$requirements = [
    'name="article_ids[]"' => 'Auswahlkästchen pro Artikel fehlt.',
    'id="select-all-articles"' => 'Alle-auswählen-Feld fehlt.',
    'name="bulk_action"' => 'Auswahl der Sammelaktion fehlt.',
    'value="move"' => 'Aktion Verschieben fehlt.',
    'value="archive"' => 'Aktion Archivieren fehlt.',
    'name="single_archive"' => 'POST-Einzelarchivierung fehlt.',
    'value="restore"' => 'Aktion Wiederherstellen fehlt.',
    'value="delete"' => 'Aktion Löschen fehlt.',
    'name="target_category"' => 'Auswahl der Zielkategorie fehlt.',
    'verifyCsrf($token)' => 'CSRF-Prüfung für Sammelaktionen fehlt.',
    "bulkMoveArticles(" => 'Serveraktion zum Verschieben fehlt.',
    "archiveArticles(" => 'Serveraktion zum Archivieren fehlt.',
    "restoreArchivedArticles(" => 'Serveraktion zum Wiederherstellen fehlt.',
    "permanentlyDeleteArchivedArticles(" => 'Geschütztes endgültiges Löschen fehlt.',
    "normalizeArticleStatus(" => 'Status-Allowlist fehlt.',
    'h($articleStatus)' => 'Der Status wird im HTML-Attribut nicht escaped.',
    "name=\"confirm_delete\"" => 'Serverseitige Löschbestätigung fehlt.',
];

foreach ($requirements as $needle => $message) {
    if (!str_contains($source, $needle)) {
        fwrite(STDERR, "FEHLER: {$message}\n");
        exit(1);
    }
}

echo "OK: Mehrfachauswahl und Sammelaktionen sind im Backend vorhanden.\n";
