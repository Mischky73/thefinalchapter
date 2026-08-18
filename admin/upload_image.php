<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/image_upload.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

function uploadResponse(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    uploadResponse(405, ['ok' => false, 'error' => 'Nur POST ist erlaubt.']);
}

$headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$formToken = $_POST['csrf_token'] ?? '';
$token = is_string($headerToken) && $headerToken !== ''
    ? $headerToken
    : (is_string($formToken) ? $formToken : '');
if (!verifyCsrf($token)) {
    uploadResponse(403, ['ok' => false, 'error' => 'Ungültige Anfrage. Bitte die Seite neu laden.']);
}

$file = $_FILES['image'] ?? null;
if (!is_array($file)) {
    uploadResponse(400, ['ok' => false, 'error' => 'Bitte ein Bild auswählen.']);
}

$result = storeArticleImage($file, __DIR__ . '/../assets/img/uploads');
if (!$result['ok']) {
    uploadResponse(422, ['ok' => false, 'error' => $result['error'] ?? 'Upload fehlgeschlagen.']);
}

uploadResponse(201, ['ok' => true, 'url' => $result['url']]);
