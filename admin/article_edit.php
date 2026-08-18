<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$requestedIds = normalizeArticleIds([$_GET['id'] ?? null]);
$id      = $requestedIds[0] ?? null;
$article = $id ? getArticleById($id) : null;
$isArchived = ($article['status'] ?? null) === 'archived';
$cats    = getAllCategories();
$error   = '';
$user    = currentUser();
$postString = static function (string $key, string $default = ''): string {
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : '';
};

// Formular absenden
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $postString('csrf_token');
    if (!verifyCsrf($token)) {
        $error = 'Ungültige Anfrage.';
    } else {
        $createdAtInput = $postString('created_at');
        $createdAtDate = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $createdAtInput);
        $createdAtValid = $createdAtDate
            && $createdAtDate->format('Y-m-d\\TH:i') === $createdAtInput;

        $data = [
            'title'          => $postString('title'),
            'slug'           => $postString('slug'),
            'content'        => sanitizeArticleHtml($postString('content')),
            'excerpt'        => $postString('excerpt'),
            'category_id'    => (int)$postString('category_id', '0'),
            'author'         => $postString('author', (string)$user['username']),
            'featured_image' => $postString('featured_image'),
            'status'         => $isArchived
                ? 'archived'
                : (in_array($_POST['status'] ?? '', ['draft','published'], true) ? $_POST['status'] : 'draft'),
            'created_at'     => $createdAtValid ? $createdAtDate->format('Y-m-d H:i:s') : '',
        ];
        if (!$createdAtValid) {
            $error = 'Bitte ein gültiges Erstellungsdatum mit Uhrzeit eingeben.';
        } elseif (!$data['title'] || !$data['slug'] || !$data['category_id']) {
            $error = 'Titel, Slug und Kategorie sind Pflichtfelder.';
        } else {
            $edit_id = $id ?: null;
            if (saveArticle($data, $edit_id)) {
                header('Location: ' . SITE_URL . '/admin/articles.php?msg=saved');
                exit;
            } else {
                $error = 'Fehler beim Speichern. Slug eventuell schon vergeben?';
            }
        }
    }
}

$csrf = csrfToken();
$a = $article ?? [];
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $id ? 'Bearbeiten' : 'Neu' ?> – <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-wrap">

  <?php $adminActive = 'new'; require __DIR__ . '/partials/sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-header">
      <h1><?= $id ? 'Artikel bearbeiten' : 'Neuer Artikel' ?></h1>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" id="article-form" data-upload-url="<?= h(SITE_URL) ?>/admin/upload_image.php" style="max-width:1000px">
      <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">

      <div class="form-group">
        <label for="title">Titel *</label>
        <input type="text" id="title" name="title" class="form-control"
               value="<?= h($_POST['title'] ?? $a['title'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label for="slug">Slug (URL) * <small style="font-weight:400;text-transform:none;color:var(--text-muted)">(wird auto-generiert)</small></label>
        <input type="text" id="slug" name="slug" class="form-control"
               value="<?= h($_POST['slug'] ?? $a['slug'] ?? '') ?>" required>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="form-group">
          <label for="category_id">Kategorie *</label>
          <select id="category_id" name="category_id" class="form-control" required>
            <option value="">– bitte wählen –</option>
            <?php foreach ($cats as $cat): ?>
            <option value="<?= $cat['id'] ?>"
              <?= (($a['category_id'] ?? 0) == $cat['id'] || ($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
              <?= $cat['parent_id'] ? '↳ ' : '' ?><?= h($cat['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="status">Status</label>
          <?php if ($isArchived): ?>
            <input type="hidden" name="status" value="archived">
            <input type="text" id="status" class="form-control" value="Archiviert" disabled>
            <small style="color:var(--text-muted)">Zum Veröffentlichen oder als Entwurf bitte zuerst im Artikelarchiv wiederherstellen.</small>
          <?php else: ?>
            <select id="status" name="status" class="form-control">
              <option value="draft" <?= (($_POST['status'] ?? $a['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Entwurf</option>
              <option value="published" <?= (($_POST['status'] ?? $a['status'] ?? '') === 'published') ? 'selected' : '' ?>>Veröffentlicht</option>
            </select>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-group">
        <label for="created_at">Erstellungsdatum</label>
        <input type="datetime-local" id="created_at" name="created_at" class="form-control"
               value="<?= h($_POST['created_at'] ?? (!empty($a['created_at']) ? date('Y-m-d\\TH:i', strtotime($a['created_at'])) : date('Y-m-d\\TH:i'))) ?>"
               step="60" required>
      </div>

      <div class="form-group">
        <label for="author">Autor</label>
        <input type="text" id="author" name="author" class="form-control"
               value="<?= h($_POST['author'] ?? $a['author'] ?? $user['username']) ?>">
      </div>

      <?php
      $featuredInput = $_POST['featured_image'] ?? $a['featured_image'] ?? '';
      $featuredValue = is_string($featuredInput) ? sanitizeLocalImagePath($featuredInput) : '';
      ?>
      <div class="form-group">
        <label for="featured_image">Artikelbild</label>
        <div class="image-upload-panel" data-image-upload>
          <div class="image-upload-preview<?= $featuredValue === '' ? ' is-empty' : '' ?>">
            <img src="<?= h($featuredValue) ?>" alt="Bildvorschau" data-image-preview <?= $featuredValue === '' ? 'hidden' : '' ?>>
            <span data-image-placeholder <?= $featuredValue !== '' ? 'hidden' : '' ?>>Noch kein Artikelbild ausgewählt</span>
          </div>
          <div class="image-upload-controls">
            <input type="text" id="featured_image" name="featured_image" class="form-control"
                   value="<?= h($featuredValue) ?>" placeholder="Bild hochladen oder Pfad einfügen">
            <label class="btn btn-secondary image-upload-button" for="featured_image_file">Bild auswählen</label>
            <input type="file" id="featured_image_file" class="image-upload-file" accept="image/jpeg,image/png,image/webp,image/gif">
            <p class="image-upload-help">JPG, PNG, WebP oder GIF · maximal 8 MB · Datei hierher ziehen ist ebenfalls möglich.</p>
            <p class="image-upload-status" data-upload-status aria-live="polite"></p>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="excerpt">Kurzbeschreibung (Excerpt)</label>
        <textarea id="excerpt" name="excerpt" class="form-control" rows="3"><?= h($_POST['excerpt'] ?? $a['excerpt'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label for="content">Inhalt *</label>
        <div class="wysiwyg-shell" data-wysiwyg>
          <div class="wysiwyg-toolbar" role="toolbar" aria-label="Text formatieren" hidden>
            <button type="button" data-command="formatBlock" data-value="p" title="Absatz">Absatz</button>
            <button type="button" data-command="formatBlock" data-value="h2" title="Überschrift 2">H2</button>
            <button type="button" data-command="formatBlock" data-value="h3" title="Überschrift 3">H3</button>
            <span class="wysiwyg-separator"></span>
            <button type="button" data-command="bold" title="Fett"><strong>Fett</strong></button>
            <button type="button" data-command="italic" title="Kursiv"><em>Kursiv</em></button>
            <button type="button" data-command="underline" title="Unterstrichen"><u>U</u></button>
            <button type="button" data-command="insertUnorderedList" title="Aufzählung">Liste</button>
            <button type="button" data-command="insertOrderedList" title="Nummerierte Liste">1. Liste</button>
            <button type="button" data-command="formatBlock" data-value="blockquote" title="Zitat">Zitat</button>
            <button type="button" data-action="link" title="Link einfügen">Link</button>
            <button type="button" data-command="insertHorizontalRule" title="Trennlinie">Linie</button>
            <button type="button" data-action="inline-image" title="Bild in den Text einfügen">Bild</button>
            <button type="button" data-command="removeFormat" title="Formatierung entfernen">Format löschen</button>
          </div>
          <textarea id="content" name="content" class="form-control wysiwyg-source" rows="20"><?= h(sanitizeArticleHtml($postString('content', (string)($a['content'] ?? '')))) ?></textarea>
          <div class="wysiwyg-editor" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Artikelinhalt" hidden></div>
          <input type="file" class="wysiwyg-inline-image" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
          <p class="image-upload-status" data-inline-upload-status aria-live="polite"></p>
        </div>
      </div>

      <div style="display:flex;gap:1rem;flex-wrap:wrap">
        <button type="submit" class="btn btn-primary">Speichern</button>
        <a href="articles.php" class="btn btn-secondary">Abbrechen</a>
        <?php if ($id && !empty($a['slug'])): ?>
          <a href="<?= SITE_URL ?>/article.php?slug=<?= h($a['slug']) ?>" target="_blank" class="btn btn-secondary">Vorschau</a>
        <?php endif; ?>
      </div>
    </form>
  </main>

</div>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script src="<?= SITE_URL ?>/assets/js/admin-editor.js?v=<?= filemtime(__DIR__ . '/../assets/js/admin-editor.js') ?>"></script>
</body>
</html>
