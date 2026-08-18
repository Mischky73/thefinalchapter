<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$message = 'bulk-invalid-action';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = normalizeArticleIds([$_POST['id'] ?? null]);
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCsrf($csrf)) {
        $message = 'bulk-csrf';
    } elseif ($ids !== [] && archiveArticles($ids) === 1) {
        $message = 'archived';
    }
}

header('Location: ' . SITE_URL . '/admin/articles.php?msg=' . urlencode($message));
exit;
