<?php

declare(strict_types=1);

/**
 * Prüft und speichert ein tatsächlich decodierbares Rasterbild unter einem zufälligen Namen.
 * SVG wird bewusst nicht akzeptiert, da es aktiven Inhalt enthalten kann.
 *
 * @return array{ok:bool,url?:string,path?:string,error?:string}
 */
function storeArticleImage(
    array $file,
    string $uploadRoot,
    string $publicBase = '/assets/img/uploads',
    bool $requireHttpUpload = true
): array {
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    $size = $file['size'] ?? 0;
    $tmpName = $file['tmp_name'] ?? '';
    if (!is_int($error) || $error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => $error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE
            ? 'Das Bild ist zu groß.' : 'Das Bild konnte nicht hochgeladen werden.'];
    }
    if (!is_int($size) || $size < 1 || $size > 8 * 1024 * 1024 || !is_string($tmpName) || !is_file($tmpName)) {
        return ['ok' => false, 'error' => 'Erlaubt sind Bilder bis maximal 8 MB.'];
    }
    if ($requireHttpUpload && !is_uploaded_file($tmpName)) {
        return ['ok' => false, 'error' => 'Die Upload-Datei ist ungültig.'];
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmpName);
    $imageInfo = @getimagesize($tmpName);
    if (!isset($allowed[$mime]) || !is_array($imageInfo) || ($imageInfo['mime'] ?? '') !== $mime) {
        return ['ok' => false, 'error' => 'Erlaubt sind nur echte JPG-, PNG-, WebP- oder GIF-Bilder.'];
    }
    $width = (int)($imageInfo[0] ?? 0);
    $height = (int)($imageInfo[1] ?? 0);
    if ($width < 1 || $height < 1 || $width > 12000 || $height > 12000 || ($width * $height) > 60000000) {
        return ['ok' => false, 'error' => 'Die Bildabmessungen sind ungültig oder zu groß.'];
    }

    $subdirectory = date('Y/m');
    $destinationDirectory = rtrim($uploadRoot, '/') . '/' . $subdirectory;
    if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0755, true) && !is_dir($destinationDirectory)) {
        return ['ok' => false, 'error' => 'Das Upload-Verzeichnis konnte nicht erstellt werden.'];
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $destination = $destinationDirectory . '/' . $filename;
    $stored = $requireHttpUpload
        ? move_uploaded_file($tmpName, $destination)
        : rename($tmpName, $destination);
    if (!$stored) {
        return ['ok' => false, 'error' => 'Das Bild konnte nicht gespeichert werden.'];
    }
    @chmod($destination, 0644);

    return [
        'ok' => true,
        'url' => rtrim($publicBase, '/') . '/' . $subdirectory . '/' . $filename,
        'path' => $destination,
    ];
}
