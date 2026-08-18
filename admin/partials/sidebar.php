<?php
// Gemeinsame Admin-Sidebar
// Erwartet: $adminActive (string) – aktive Seite: 'dashboard','articles','new','categories'
if (!isset($adminActive)) $adminActive = '';
?>
<aside class="admin-sidebar">
  <div class="admin-logo">
    The Final Chapter
    <small>Redaktion</small>
  </div>
  <ul class="admin-nav">
    <li><a href="<?= SITE_URL ?>/admin/" class="<?= $adminActive==='dashboard'?'active':'' ?>">Dashboard</a></li>
    <li><a href="<?= SITE_URL ?>/admin/articles.php" class="<?= $adminActive==='articles'?'active':'' ?>">Artikel</a></li>
    <li><a href="<?= SITE_URL ?>/admin/articles.php?status=draft" class="<?= $adminActive==='drafts'?'active':'' ?>">Entwürfe prüfen</a></li>
    <li><a href="<?= SITE_URL ?>/admin/article_edit.php" class="<?= $adminActive==='new'?'active':'' ?>">Neuer Artikel</a></li>
    <li><a href="<?= SITE_URL ?>/admin/categories.php" class="<?= $adminActive==='categories'?'active':'' ?>">Kategorien</a></li>
    <li><a href="<?= SITE_URL ?>/admin/team.php" class="<?= $adminActive==='team'?'active':'' ?>">Teamseiten</a></li>
    <li><a href="<?= SITE_URL ?>/admin/users.php" class="<?= $adminActive==='users'?'active':'' ?>">Benutzer</a></li>
    <li><a href="<?= SITE_URL ?>/admin/menu.php" class="<?= $adminActive==='menu'?'active':'' ?>">Menü</a></li>
    <li><a href="<?= SITE_URL ?>" target="_blank">Website ansehen</a></li>
    <li><a href="<?= SITE_URL ?>/admin/logout.php">Ausloggen</a></li>
  </ul>
</aside>
