<?php
require_once __DIR__ . '/../includes/config.php';

function expectFooter(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FEHLER: {$message}\n");
        exit(1);
    }
}

ob_start();
require __DIR__ . '/../includes/partials/footer.php';
$html = ob_get_clean();
$footerSource = file_get_contents(__DIR__ . '/../includes/partials/footer.php');
$css = file_get_contents(__DIR__ . '/../assets/css/style.css');

expectFooter(str_contains($html, '<footer class="site-footer">'),
    'Der öffentliche Footer fehlt.');
expectFooter(str_contains($html, 'href="' . SITE_URL . '/kontakt.php"'),
    'Der Footer benötigt einen Kontakt-Link.');
expectFooter(str_contains($html, 'href="' . SITE_URL . '/impressum.php"'),
    'Der Footer benötigt einen Impressum-Link.');
expectFooter(str_contains($html, 'href="' . SITE_URL . '/datenschutz.php"'),
    'Der Footer benötigt einen Datenschutz-Link.');
expectFooter(str_contains($html, 'The Final Chapter'),
    'Der Magazinname fehlt im Footer.');
expectFooter(str_contains($html, '© ' . date('Y')),
    'Das Copyright-Jahr muss automatisch aktuell sein.');
expectFooter(str_contains($html, 'href="' . SITE_URL . '/kontakt.php"'),
    'Der Footer benötigt einen Kontakt-Link statt eines durch Cloudflare umgeschriebenen mailto-Links.');
expectFooter(str_contains($footerSource, 'htmlspecialchars'),
    'SITE_URL muss vor der Ausgabe in HTML-Attributen maskiert werden.');
expectFooter(!preg_match('/var\(--(?:light|red|muted)\)/', $css),
    'Der Footer-/Impressumsbereich darf keine undefinierten CSS-Variablen verwenden.');
expectFooter(!preg_match('/[📧📘⚡📊✏️📁🌐🚪🗑️]/u', $html),
    'Der Footer darf keine UI-Emojis enthalten.');

fwrite(STDOUT, "OK: Footer enthält Kontakt, Impressum, Datenschutz und Copyright.\n");
