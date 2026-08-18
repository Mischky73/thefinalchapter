<?php
function expectDeploymentSecurity(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FEHLER: {$message}\n");
        exit(1);
    }
}

$root = dirname(__DIR__);
$htaccess = file_get_contents($root . '/.htaccess');
$install = file_get_contents($root . '/install.sql');
$readme = file_get_contents($root . '/README.md');
$deploy = file_get_contents($root . '/DEPLOY.md');
$functions = file_get_contents($root . '/includes/functions.php');

expectDeploymentSecurity((bool)preg_match('/<FilesMatch[^>]*\.(?=[^>]*sql)(?=[^>]*sh)(?=[^>]*md)[^>]*>/is', $htaccess),
    'SQL-, Shell- und Markdown-Dateien werden im Webroot nicht gemeinsam ausdrücklich gesperrt.');
expectDeploymentSecurity(!preg_match('/INSERT\s+IGNORE\s+INTO\s+users/i', $install),
    'install.sql darf keinen festen Admin-Account enthalten.');
expectDeploymentSecurity(!str_contains($install, '@') && !str_contains($install, '$2y$'),
    'install.sql enthält E-Mail- oder Passwort-Hash-Daten.');
expectDeploymentSecurity(!str_contains($readme, 'TLC2026!'),
    'README dokumentiert noch feste Standardzugangsdaten.');
expectDeploymentSecurity(!preg_match('/(?:Benutzer(?:name)?|Passwort|Kennwort)\s*:\s*\S+/iu', $deploy)
    && !preg_match('/\$2[ayb]\$\d{2}\$/', $deploy)
    && !preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', $deploy),
    'DEPLOY.md enthält Zugangsdaten, Passwort-Hashes oder E-Mail-Adressen.');
expectDeploymentSecurity(!is_file($root . '/preview.html') && !is_file($root . '/vorschau.html'),
    'Veraltete, fremdgehostete Vorschauseiten liegen noch im Webroot.');
expectDeploymentSecurity(str_contains($functions, "if (!class_exists('DOMDocument'))")
    && !str_contains($functions, "strip_tags(\$html, '<p><br>"),
    'Der Sanitizer besitzt weiterhin einen unsicheren Regex-/strip_tags-Fallback.');

echo "OK: Deploymentdateien und Sanitizer-Voraussetzungen sind abgesichert.\n";