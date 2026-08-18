<?php
$_SERVER['HTTP_HOST'] = '127.0.0.1:7788';

function expectDatenschutz(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FEHLER: {$message}\n");
        exit(1);
    }
}

$page = __DIR__ . '/../datenschutz.php';
expectDatenschutz(is_file($page),
    'Die Datenschutzerklärung fehlt.');

ob_start();
require $page;
$html = ob_get_clean();
$text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

expectDatenschutz(str_contains($text, 'Datenschutzerklärung'),
    'Die Überschrift Datenschutzerklärung fehlt.');
expectDatenschutz(str_contains($text, 'Michael Jakob'),
    'Der Verantwortliche fehlt.');
expectDatenschutz(str_contains($text, 'michajakob@t-online.de'),
    'Die Kontaktmöglichkeit des Verantwortlichen fehlt.');
expectDatenschutz(str_contains($text, 'Cloudflare'),
    'Die Verarbeitung durch Cloudflare muss erläutert werden.');
expectDatenschutz(str_contains($text, 'EU-U.S. Data Privacy Framework')
        && str_contains($text, 'Standardvertragsklauseln'),
    'Die Garantien für mögliche Cloudflare-Drittlandübermittlungen müssen konkret benannt werden.');
expectDatenschutz(str_contains($text, 'Server-Logfiles'),
    'Die Server-Logfiles müssen erläutert werden.');
expectDatenschutz(str_contains($text, 'E-Mail'),
    'Die Verarbeitung bei Kontaktaufnahme muss erläutert werden.');
expectDatenschutz(str_contains($text, 'keine Analyse- oder Werbe-Cookies'),
    'Der Verzicht auf Analyse- und Werbe-Cookies muss klar genannt werden.');
expectDatenschutz(str_contains($text, 'lokal eingebunden'),
    'Die lokale Einbindung der Schriftarten muss erläutert werden.');

$sourceFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/..', FilesystemIterator::SKIP_DOTS)
);
foreach ($sourceFiles as $sourceFile) {
    if (!$sourceFile->isFile()
        || !in_array($sourceFile->getExtension(), ['php', 'css'], true)
        || str_contains($sourceFile->getPathname(), DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR)) {
        continue;
    }
    $source = file_get_contents($sourceFile->getPathname());
    expectDatenschutz(!str_contains($source, 'fonts.googleapis.com') && !str_contains($source, 'fonts.gstatic.com'),
        'Google Fonts dürfen nicht direkt von Drittservern geladen werden: ' . $sourceFile->getPathname());
}

expectDatenschutz(str_contains($text, 'Art. 15') && str_contains($text, 'Art. 21'),
    'Die Betroffenenrechte müssen aufgeführt werden.');
expectDatenschutz(str_contains($text, 'Thüringer Landesbeauftragte'),
    'Die zuständige Datenschutzaufsicht muss genannt werden.');
expectDatenschutz(!str_contains($text, '[Bitte') && !str_contains($text, '[Adresse'),
    'Die Datenschutzerklärung darf keine Platzhalter enthalten.');

fwrite(STDOUT, "OK: Datenschutzerklärung enthält die erforderlichen Angaben.\n");
