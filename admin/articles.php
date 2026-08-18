<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$statusFilter = $_GET['status'] ?? '';
if (!in_array($statusFilter, ['', 'draft', 'published', 'archived'], true)) {
    $statusFilter = '';
}
$categoryFilter = normalizeNonNegativeIntInput($_GET['category'] ?? null);
$requestedPage  = max(1, normalizeNonNegativeIntInput($_GET['page'] ?? null, 1));
$categories     = getAllCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token           = $_POST['csrf_token'] ?? '';
    $selectedIds     = is_array($_POST['article_ids'] ?? null) ? $_POST['article_ids'] : [];
    $singleArchiveIds = normalizeArticleIds([$_POST['single_archive'] ?? null]);
    $bulkAction      = $_POST['bulk_action'] ?? '';
    $targetCategory  = normalizeNonNegativeIntInput($_POST['target_category'] ?? null);
    $deleteConfirmed = ($_POST['confirm_delete'] ?? '') === 'yes';
    $returnStatus    = $_POST['return_status'] ?? '';
    $returnCategory  = normalizeNonNegativeIntInput($_POST['return_category'] ?? null);
    $returnPage      = max(1, normalizeNonNegativeIntInput($_POST['return_page'] ?? null, 1));

    if (!in_array($returnStatus, ['', 'draft', 'published', 'archived'], true)) {
        $returnStatus = '';
    }

    $message = '';
    $affected = 0;
    if (!verifyCsrf($token)) {
        $message = 'bulk-csrf';
    } elseif ($singleArchiveIds !== []) {
        $affected = archiveArticles($singleArchiveIds);
        $message = 'archived';
    } elseif (normalizeArticleIds($selectedIds) === []) {
        $message = 'bulk-none';
    } elseif ($bulkAction === 'move') {
        $validCategoryIds = array_map(fn(array $category): int => (int)$category['id'], $categories);
        if (!in_array($targetCategory, $validCategoryIds, true)) {
            $message = 'bulk-invalid-category';
        } else {
            $affected = bulkMoveArticles($selectedIds, $targetCategory);
            $message = 'bulk-moved';
        }
    } elseif ($bulkAction === 'archive') {
        $affected = archiveArticles($selectedIds);
        $message = 'bulk-archived';
    } elseif ($bulkAction === 'restore') {
        $affected = restoreArchivedArticles($selectedIds);
        $message = 'bulk-restored';
    } elseif ($bulkAction === 'delete' && !$deleteConfirmed) {
        $message = 'bulk-confirm-required';
    } elseif ($bulkAction === 'delete') {
        $affected = permanentlyDeleteArchivedArticles($selectedIds);
        $message = 'bulk-deleted';
    } else {
        $message = 'bulk-invalid-action';
    }

    $params = ['msg' => $message, 'count' => $affected];
    if ($returnStatus !== '') {
        $params['status'] = $returnStatus;
    }
    if ($returnCategory > 0) {
        $params['category'] = $returnCategory;
    }
    if ($returnPage > 1) {
        $params['page'] = $returnPage;
    }
    header('Location: articles.php?' . http_build_query($params));
    exit;
}

$perPage        = 50;
$totalArticles  = countAdminArticlesFiltered($statusFilter, $categoryFilter);
$totalPages     = max(1, (int)ceil($totalArticles / $perPage));
$page           = min($requestedPage, $totalPages);
$offset         = ($page - 1) * $perPage;
$articles       = getAdminArticlesPage($statusFilter, $categoryFilter, $perPage, $offset);
$msg            = $_GET['msg'] ?? '';
$affectedCount  = max(0, (int)($_GET['count'] ?? 0));
$adminActive    = 'articles';
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Artikel – <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-wrap">

  <?php $adminActive = $statusFilter === 'draft' ? 'drafts' : 'articles'; require __DIR__ . '/partials/sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-header">
      <h1>Artikel verwalten</h1>
      <a href="<?= SITE_URL ?>/admin/article_edit.php" class="btn btn-primary">Neuer Artikel</a>
    </div>

    <?php if ($msg === 'saved'): ?>
      <div class="alert alert-success">Artikel gespeichert.</div>
    <?php elseif ($msg === 'archived'): ?>
      <div class="alert alert-success">Artikel archiviert.</div>
    <?php elseif ($msg === 'bulk-moved'): ?>
      <div class="alert alert-success"><?= $affectedCount ?> Artikel verschoben.</div>
    <?php elseif ($msg === 'bulk-archived'): ?>
      <div class="alert alert-success"><?= $affectedCount ?> Artikel archiviert.</div>
    <?php elseif ($msg === 'bulk-restored'): ?>
      <div class="alert alert-success"><?= $affectedCount ?> Artikel wiederhergestellt.</div>
    <?php elseif ($msg === 'bulk-deleted'): ?>
      <div class="alert alert-success"><?= $affectedCount ?> archivierte Artikel endgültig gelöscht.</div>
    <?php elseif ($msg === 'bulk-none'): ?>
      <div class="alert alert-danger">Bitte mindestens einen Artikel auswählen.</div>
    <?php elseif ($msg === 'bulk-invalid-category'): ?>
      <div class="alert alert-danger">Bitte eine gültige Zielkategorie auswählen.</div>
    <?php elseif ($msg === 'bulk-invalid-action'): ?>
      <div class="alert alert-danger">Bitte eine gültige Sammelaktion auswählen.</div>
    <?php elseif ($msg === 'bulk-confirm-required'): ?>
      <div class="alert alert-danger">Das Löschen wurde nicht bestätigt.</div>
    <?php elseif ($msg === 'bulk-csrf'): ?>
      <div class="alert alert-danger">Die Anfrage ist abgelaufen. Bitte erneut versuchen.</div>
    <?php endif; ?>

    <form method="get" class="admin-filters" aria-label="Artikel filtern">
      <div class="admin-filter-field">
        <label for="status-filter">Status</label>
        <select id="status-filter" name="status" class="form-control">
          <option value=""<?= $statusFilter === '' ? ' selected' : '' ?>>Aktive Artikel</option>
          <option value="draft"<?= $statusFilter === 'draft' ? ' selected' : '' ?>>Entwurf</option>
          <option value="published"<?= $statusFilter === 'published' ? ' selected' : '' ?>>Veröffentlicht</option>
          <option value="archived"<?= $statusFilter === 'archived' ? ' selected' : '' ?>>Archiviert</option>
        </select>
      </div>

      <div class="admin-filter-field admin-filter-category">
        <label for="category-filter">Kategorie</label>
        <select id="category-filter" name="category" class="form-control">
          <option value="0"<?= $categoryFilter === 0 ? ' selected' : '' ?>>Alle Kategorien</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?= (int)$category['id'] ?>"<?= $categoryFilter === (int)$category['id'] ? ' selected' : '' ?>>
              <?= h($category['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-primary">Filter anwenden</button>
      <?php if ($statusFilter !== '' || $categoryFilter > 0): ?>
        <a href="<?= SITE_URL ?>/admin/articles.php" class="btn btn-secondary">Filter zurücksetzen</a>
      <?php endif; ?>
    </form>

    <div class="admin-filter-result">
      <?= $totalArticles ?> Artikel gefunden · Seite <?= $page ?> von <?= $totalPages ?>
    </div>

    <form method="post" id="article-bulk-form">
      <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="return_status" value="<?= h($statusFilter) ?>">
      <input type="hidden" name="return_category" value="<?= $categoryFilter ?>">
      <input type="hidden" name="return_page" value="<?= $page ?>">
      <input type="hidden" id="confirm-delete" name="confirm_delete" value="no">

      <div class="admin-bulk-actions">
        <div class="admin-filter-field">
          <label for="bulk-action">Sammelaktion</label>
          <select id="bulk-action" name="bulk_action" class="form-control" required>
            <option value="">Bitte auswählen</option>
            <option value="move">In Kategorie verschieben</option>
            <?php if ($statusFilter === 'archived'): ?>
              <option value="restore">Wiederherstellen</option>
              <option value="delete">Endgültig löschen</option>
            <?php else: ?>
              <option value="archive">Archivieren</option>
            <?php endif; ?>
          </select>
        </div>

        <div class="admin-filter-field admin-bulk-category">
          <label for="target-category">Zielkategorie</label>
          <select id="target-category" name="target_category" class="form-control">
            <option value="0">Bitte auswählen</option>
            <?php foreach ($categories as $category): ?>
              <option value="<?= (int)$category['id'] ?>"><?= h($category['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="btn btn-primary">Anwenden</button>
        <span id="selected-article-count" class="admin-bulk-count">0 ausgewählt</span>
      </div>

      <table class="admin-table">
        <thead>
          <tr>
            <th class="admin-check-cell">
              <input type="checkbox" id="select-all-articles" aria-label="Alle angezeigten Artikel auswählen">
            </th>
            <th>Titel</th>
            <th>Kategorie</th>
            <th>Autor</th>
            <th>Status</th>
            <th>Datum</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($articles as $a): ?>
          <?php $articleStatus = normalizeArticleStatus($a['status'] ?? null); ?>
          <tr>
            <td class="admin-check-cell">
              <input type="checkbox" class="article-select" name="article_ids[]"
                     value="<?= (int)$a['id'] ?>" aria-label="<?= h($a['title']) ?> auswählen">
            </td>
            <td>
              <?php if ($articleStatus !== 'published'): ?>
                <a href="<?= SITE_URL ?>/admin/article_edit.php?id=<?= (int)$a['id'] ?>"
                   style="color:var(--text)"><?= h($a['title']) ?></a>
              <?php else: ?>
                <a href="<?= SITE_URL ?>/article.php?slug=<?= h($a['slug']) ?>"
                   target="_blank" style="color:var(--text)"><?= h($a['title']) ?></a>
              <?php endif; ?>
            </td>
            <td style="color:var(--text-muted);font-size:.8rem"><?= h($a['category_name'] ?? '–') ?></td>
            <td style="color:var(--text-muted);font-size:.8rem"><?= h($a['author']) ?></td>
            <td>
              <span class="badge badge-<?= h($articleStatus) ?>">
                <?= $articleStatus === 'archived' ? 'Archiviert' : ($articleStatus === 'published' ? 'Online' : 'Entwurf') ?>
              </span>
            </td>
            <td style="color:var(--text-muted);font-size:.78rem;white-space:nowrap">
              <?= date('d.m.Y', strtotime($a['created_at'])) ?>
            </td>
            <td style="white-space:nowrap">
              <a href="<?= SITE_URL ?>/admin/article_edit.php?id=<?= $a['id'] ?>"
                 class="btn btn-secondary btn-sm">Bearbeiten</a>
              <?php if ($articleStatus !== 'archived'): ?>
              <button type="submit" name="single_archive" value="<?= (int)$a['id'] ?>"
                      class="btn btn-danger btn-sm" formnovalidate
                      data-single-archive>Archivieren</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($articles)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem">Noch keine Artikel vorhanden.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </form>

    <?php if ($totalPages > 1): ?>
      <nav class="pagination" aria-label="Seitennavigation">
        <?php if ($page > 1): ?>
          <a href="<?= h(buildAdminArticlePageUrl($page - 1, $statusFilter, $categoryFilter)) ?>"
             class="prev-next" aria-label="Vorherige Seite">‹</a>
        <?php endif; ?>

        <?php if ($page > 3): ?>
          <a href="<?= h(buildAdminArticlePageUrl(1, $statusFilter, $categoryFilter)) ?>">1</a>
          <?php if ($page > 4): ?><span class="dots">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <?php if ($i === $page): ?>
            <span class="current" aria-current="page"><?= $i ?></span>
          <?php else: ?>
            <a href="<?= h(buildAdminArticlePageUrl($i, $statusFilter, $categoryFilter)) ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages - 2): ?>
          <?php if ($page < $totalPages - 3): ?><span class="dots">…</span><?php endif; ?>
          <a href="<?= h(buildAdminArticlePageUrl($totalPages, $statusFilter, $categoryFilter)) ?>"><?= $totalPages ?></a>
        <?php endif; ?>

        <?php if ($page < $totalPages): ?>
          <a href="<?= h(buildAdminArticlePageUrl($page + 1, $statusFilter, $categoryFilter)) ?>"
             class="prev-next" aria-label="Nächste Seite">›</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  </main>

</div>
<script>
(() => {
  const form = document.getElementById('article-bulk-form');
  if (!form) return;

  const selectAll = document.getElementById('select-all-articles');
  const articleBoxes = Array.from(form.querySelectorAll('.article-select'));
  const action = document.getElementById('bulk-action');
  const targetCategory = document.getElementById('target-category');
  const selectedCount = document.getElementById('selected-article-count');
  const confirmDelete = document.getElementById('confirm-delete');

  const updateSelection = () => {
    const checked = articleBoxes.filter(box => box.checked).length;
    selectedCount.textContent = `${checked} ausgewählt`;
    if (selectAll) {
      selectAll.checked = articleBoxes.length > 0 && checked === articleBoxes.length;
      selectAll.indeterminate = checked > 0 && checked < articleBoxes.length;
    }
  };

  selectAll?.addEventListener('change', () => {
    articleBoxes.forEach(box => { box.checked = selectAll.checked; });
    updateSelection();
  });
  articleBoxes.forEach(box => box.addEventListener('change', updateSelection));

  form.addEventListener('submit', event => {
    confirmDelete.value = 'no';
    if (event.submitter?.matches('[data-single-archive]')) {
      if (!confirm('Diesen Artikel archivieren?')) {
        event.preventDefault();
      }
      return;
    }
    const checked = articleBoxes.filter(box => box.checked).length;
    if (checked === 0) {
      event.preventDefault();
      alert('Bitte mindestens einen Artikel auswählen.');
      return;
    }
    if (action.value === 'move' && targetCategory.value === '0') {
      event.preventDefault();
      alert('Bitte eine Zielkategorie auswählen.');
      return;
    }
    if (action.value === 'delete') {
      if (!confirm(`${checked} archivierte Artikel wirklich endgültig löschen? Dieser Vorgang kann nicht rückgängig gemacht werden.`)) {
        event.preventDefault();
        return;
      }
      confirmDelete.value = 'yes';
    }
  });

  updateSelection();
})();
</script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
