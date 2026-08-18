<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

function expectEditor(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FEHLER: {$message}\n");
        exit(1);
    }
}

$editorSource = file_get_contents(__DIR__ . '/../admin/article_edit.php');
$uploadSource = @file_get_contents(__DIR__ . '/../admin/upload_image.php') ?: '';
$scriptSource = @file_get_contents(__DIR__ . '/../assets/js/admin-editor.js') ?: '';

expectEditor(str_contains($editorSource, 'class="wysiwyg-editor"'), 'Der visuelle Editor fehlt im Artikelformular.');
expectEditor(str_contains($editorSource, 'admin-editor.js'), 'Das lokale Editor-Skript ist nicht eingebunden.');
expectEditor(str_contains($editorSource, 'type="file"') && str_contains($editorSource, 'accept="image/'),
    'Die einfache Bildauswahl fehlt.');
expectEditor(str_contains($editorSource, 'contenteditable="true"') && str_contains($scriptSource, 'editor.innerHTML'),
    'Der Editor synchronisiert den visuellen Inhalt nicht.');
expectEditor(str_contains($uploadSource, 'requireLogin()'), 'Der Upload ist nicht auf angemeldete Redakteure begrenzt.');
expectEditor(str_contains($uploadSource, "verifyCsrf"), 'Der Upload besitzt keinen CSRF-Schutz.');
expectEditor(str_contains($uploadSource, "!== 'POST'"), 'Der Upload akzeptiert andere Methoden als POST.');

expectEditor(function_exists('sanitizeArticleHtml'), 'Die serverseitige HTML-Bereinigung fehlt.');
$dirty = '<h2 onclick="alert(1)">Titel</h2><p>Text <strong>fett</strong><script>alert(1)</script>'
    . '<a href="javascript:alert(2)" target="_blank">böse</a>'
    . '<img src="/assets/img/uploads/test.webp" onerror="alert(3)" alt="Test"></p>';
$clean = sanitizeArticleHtml($dirty);
expectEditor(str_contains($clean, '<h2>Titel</h2>'), 'Erlaubte Überschriften werden entfernt.');
expectEditor(str_contains($clean, '<strong>fett</strong>'), 'Erlaubte Textformatierung wird entfernt.');
expectEditor(!str_contains($clean, '<script') && !str_contains($clean, 'onclick') && !str_contains($clean, 'onerror'),
    'Aktive Script-Inhalte oder Eventhandler bleiben erhalten.');
expectEditor(!str_contains($clean, 'javascript:'), 'Gefährliche Link-Protokolle bleiben erhalten.');
expectEditor(str_contains($clean, '/assets/img/uploads/test.webp'), 'Sichere lokale Bildpfade werden entfernt.');
$externalAssets = sanitizeArticleHtml('<img src="https://evil.example/track.gif"><iframe src="https://www.youtube.com/embed/test"></iframe>');
expectEditor(!str_contains($externalAssets, 'evil.example') && !str_contains($externalAssets, '<iframe'),
    'Fremdgehostete Bilder oder Iframes bleiben im Artikelinhalt erhalten.');
$encodedTraversal = sanitizeArticleHtml('<img src="/assets/img/%252e%252e/admin/x.jpg">');
expectEditor(!str_contains($encodedTraversal, '%252e%252e'), 'Kodierte Pfadmanipulation bleibt als Bildquelle erhalten.');
expectEditor(articleImage('https://evil.example/track.gif') === '/assets/img/article-fallback.svg',
    'Fremdgehostete Artikelbilder müssen auf das lokale Standardbild zurückfallen.');
expectEditor(articleImage('/assets/img/%2e%2e/admin/x.jpg') === '/assets/img/article-fallback.svg',
    'Kodierte Pfadmanipulation darf nicht als Artikelbild ausgegeben werden.');

require_once __DIR__ . '/../includes/image_upload.php';
$tempDir = sys_get_temp_dir() . '/tfc-upload-test-' . bin2hex(random_bytes(5));
mkdir($tempDir, 0700, true);
$png = $tempDir . '/bild.txt';
file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
$result = storeArticleImage([
    'name' => '../../angriff.php',
    'tmp_name' => $png,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($png),
], $tempDir . '/uploads', '/assets/img/uploads-test', false);
expectEditor($result['ok'] === true, 'Ein gültiges PNG-Testbild wurde abgewiesen.');
expectEditor(str_ends_with($result['url'], '.png'), 'Die Dateiendung wird nicht aus dem geprüften MIME-Typ erzeugt.');
expectEditor(!str_contains($result['url'], 'angriff') && !str_contains($result['url'], '..'),
    'Der unsichere ursprüngliche Dateiname wurde übernommen.');
expectEditor(is_file($result['path']), 'Das geprüfte Bild wurde nicht gespeichert.');

$fake = $tempDir . '/fake.jpg';
file_put_contents($fake, '<?php echo "x";');
$invalid = storeArticleImage([
    'name' => 'fake.jpg', 'tmp_name' => $fake, 'error' => UPLOAD_ERR_OK, 'size' => filesize($fake),
], $tempDir . '/uploads', '/assets/img/uploads-test', false);
expectEditor($invalid['ok'] === false, 'Eine als JPG getarnte PHP-Datei wurde akzeptiert.');

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($iterator as $item) {
    $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
}
rmdir($tempDir);

echo "OK: WYSIWYG-Editor, HTML-Bereinigung und sicherer Bildupload sind vorhanden.\n";
