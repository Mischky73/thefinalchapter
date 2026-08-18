<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$stats = getStats();
$user  = currentUser();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard – <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-wrap">

  <?php $adminActive = 'dashboard'; require __DIR__ . '/partials/sidebar.php'; ?>

  <!-- Hauptinhalt -->
  <main class="admin-main">
    <div class="admin-header">
      <h1>Dashboard</h1>
      <p style="color:var(--text-muted);font-size:.85rem">
        <?= h($user['username']) ?> &mdash; <?= date('d.m.Y, H:i') ?> Uhr
      </p>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-num"><?= $stats['articles_published'] ?></div>
        <div class="stat-label">Veröffentlicht</div>
      </div>
      <div class="stat-card">
        <div class="stat-num"><?= $stats['articles_draft'] ?></div>
        <div class="stat-label">Entwürfe</div>
      </div>
      <div class="stat-card">
        <div class="stat-num"><?= $stats['articles_total'] ?></div>
        <div class="stat-label">Artikel gesamt</div>
      </div>
      <div class="stat-card">
        <div class="stat-num"><?= $stats['categories'] ?></div>
        <div class="stat-label">Kategorien</div>
      </div>
    </div>

    <div style="display:flex;gap:.75rem;flex-wrap:wrap">
      <a href="<?= SITE_URL ?>/admin/article_edit.php" class="btn btn-primary">Neuer Artikel</a>
      <a href="<?= SITE_URL ?>/admin/articles.php" class="btn btn-secondary">Alle Artikel</a>
      <a href="<?= SITE_URL ?>/admin/categories.php" class="btn btn-secondary">Kategorien</a>
      <a href="<?= SITE_URL ?>/admin/team.php" class="btn btn-secondary">Teamseiten</a>
      <a href="<?= SITE_URL ?>/admin/menu.php" class="btn btn-secondary">Menüverwaltung</a>
      <a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-secondary">Benutzer</a>
    </div>
  </main>

</div>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
