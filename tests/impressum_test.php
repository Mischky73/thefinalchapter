<?php
$_SERVER['HTTP_HOST'] = '127.0.0.1:7788';

function expectImpressum(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FEHLER: {$message}\n");
        exit(1);
    }
}

ob_start();
require __DIR__ . '/../impressum.php';
$html = ob_get_clean();
$text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

expectImpressum(str_contains($text, 'The Final Chapter.press'),
    'Die Webseitenbezeichnung fehlt.');
expectImpressum(str_contains($text, 'Michael Jakob'),
    'Der Diensteanbieter fehlt.');
expectImpressum(str_contains($text, 'Hoher Weg 29'),
    'Straße und Hausnummer fehlen.');
expectImpressum(str_contains($text, '96528 Frankenblick'),
    'PLZ und Ort fehlen.');
expectImpressum(str_contains($text, '§ 5 DDG'),
    'Die aktuelle Rechtsgrundlage nach DDG fehlt.');
expectImpressum(str_contains($text, '§ 18 Abs. 2 MStV'),
    'Die redaktionelle Verantwortlichkeit nach MStV fehlt.');
expectImpressum(str_contains($text, 'michajakob@t-online.de'),
    'Die Kontakt-E-Mail fehlt.');
expectImpressum(!str_contains($text, 'TMG'),
    'Der veraltete TMG-Verweis darf nicht mehr erscheinen.');
expectImpressum(!str_contains($text, '[Adresse') && !str_contains($text, '[Anschrift'),
    'Das Impressum darf keine Platzhalter enthalten.');

fwrite(STDOUT, "OK: Impressum enthält die aktuellen Pflichtangaben.\n");
