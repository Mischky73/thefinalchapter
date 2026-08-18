<?php

function expectSlugInput(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FEHLER: {$message}\n");
        exit(1);
    }
}

$projectRoot = realpath(__DIR__ . '/..');
$code = '$_GET["slug"] = ["unbekannt"]; chdir(' . var_export($projectRoot, true) . '); include "article.php";';
$command = escapeshellarg(PHP_BINARY) . ' -d display_errors=1 -r ' . escapeshellarg($code) . ' 2>&1';
$output = [];
$exitCode = 0;
exec($command, $output, $exitCode);
$text = implode("\n", $output);

expectSlugInput($exitCode === 0, 'Ein strukturierter Artikel-Slug darf keinen Prozessfehler auslösen.');
expectSlugInput(!str_contains($text, 'TypeError') && !str_contains($text, 'Fatal error'),
    'Ein strukturierter Artikel-Slug darf keinen HTTP-500-Fehler auslösen.');
expectSlugInput(str_contains($text, 'Dieser Beitrag wurde nicht gefunden.'),
    'Ein strukturierter Artikel-Slug soll kontrolliert als nicht gefunden behandelt werden.');

fwrite(STDOUT, "OK: Fehlerhafte Artikel-Slugs werden kontrolliert behandelt.\n");
