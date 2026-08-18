<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$error = $success = '';
$edit  = null;
$id    = (int)($_GET['id'] ?? 0);

if ($id) { $edit = getCategoryById($id); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrf($token)) {
        $error = 'Ungültige Anfrage.';
    } else {
        $action = $_POST['action'] ?? 'save';
        if ($action === 'delete') {
            $del_id = (int)($_POST['delete_id'] ?? 0);
            if ($del_id) {
                try {
                    if (deleteCategory($del_id)) {
                        $success = 'Kategorie gelöscht.';
                    } else {
                        $error = 'Kategorie nicht gefunden.';
                    }
                } catch (RuntimeException $e) {
                    $error = $e->getMessage();
                }
            }
        } else {
            $data = [
                'name'        => trim($_POST['name'] ?? ''),
                'slug'        => trim($_POST['slug'] ?? ''),
                'parent_id'   => (int)($_POST['parent_id'] ?? 0),
                'description' => trim($_POST['description'] ?? ''),
            ];
            if (!$data['name'] || !$data['slug']) {
                $error = 'Name und Slug sind Pflichtfelder.';
            } else {
                $edit_id = $id ?: null;
                if (saveCategory($data, $edit_id)) {
                    $success = 'Kategorie gespeichert.';
                    $edit = null; $id = 0;
                } else {
                    $error = 'Fehler beim Speichern.';
                }
            }
        }
    }
}

$cats = getAllCategories();
$categoryStats = [];
$statsStmt = getDB()->query(
    'SELECT category_id,
            COUNT(*) AS total,
            SUM(status = "published") AS published,
            SUM(status = "draft") AS draft,
            SUM(status = "archived") AS archived
     FROM articles
     GROUP BY category_id'
);
foreach ($statsStmt->fetchAll() as $row) {
    $categoryStats[(int)$row['category_id']] = [
        'total'     => (int)$row['total'],
        'published' => (int)$row['published'],
        'draft'     => (int)$row['draft'],
        'archived'  => (int)$row['archived'],
    ];
}
$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kategorien – <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-wrap">

  <?php $adminActive = 'categories'; require __DIR__ . '/partials/sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-header"><h1>Kategorien</h1></div>

    <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;align-items:start">

      <!-- Formular -->
      <div>
        <h2 style="font-size:1rem;margin-bottom:1rem;color:var(--text-muted)"><?= $edit ? 'Kategorie bearbeiten' : 'Neue Kategorie' ?></h2>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
          <input type="hidden" name="action" value="save">
          <div class="form-group">
            <label>Name *</label>
            <input type="text" name="name" class="form-control" value="<?= h($edit['name'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Slug *</label>
            <input type="text" name="slug" class="form-control" value="<?= h($edit['slug'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Oberkategorie</label>
            <select name="parent_id" class="form-control">
              <option value="0">– keine –</option>
              <?php foreach ($cats as $c): ?>
                <?php if (!$edit || $c['id'] !== $edit['id']): ?>
                <option value="<?= $c['id'] ?>" <?= (($edit['parent_id'] ?? 0) == $c['id']) ? 'selected' : '' ?>>
                  <?= h($c['name']) ?>
                </option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Beschreibung</label>
            <textarea name="description" class="form-control" rows="3"><?= h($edit['description'] ?? '') ?></textarea>
          </div>
          <div style="display:flex;gap:.5rem">
            <button type="submit" class="btn btn-primary">💾 Speichern</button>
            <?php if ($edit): ?>
              <a href="categories.php" class="btn btn-secondary">Abbrechen</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- Liste -->
      <div>
        <h2 style="font-size:1rem;margin-bottom:1rem;color:var(--text-muted)">Alle Kategorien</h2>
        <table class="admin-table">
          <thead><tr><th>Name</th><th>Slug</th><th style="text-align:right">Artikel</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($cats as $c): ?>
            <tr>
              <td><?= $c['parent_id'] ? '↳ ' : '' ?><?= h($c['name']) ?></td>
              <td style="color:var(--text-muted);font-size:.8rem"><?= h($c['slug']) ?></td>
              <?php $stat = $categoryStats[(int)$c['id']] ?? ['total' => 0, 'published' => 0, 'draft' => 0, 'archived' => 0]; ?>
              <td style="text-align:right;color:var(--text-muted);font-size:.85rem" title="Veröffentlicht: <?= $stat['published'] ?> · Entwürfe: <?= $stat['draft'] ?> · Archiv: <?= $stat['archived'] ?>">
                <?= $stat['total'] ?>
              </td>
              <td style="white-space:nowrap">
                <a href="?id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">✏️</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Wirklich löschen?')">
                  <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>
  </main>

</div>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
