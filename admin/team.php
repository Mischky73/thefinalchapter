<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$profiles = getEditableAuthorProfiles();
$requestedSlug = $_GET['edit'] ?? '';
$editSlug = is_string($requestedSlug) && isset($profiles[$requestedSlug])
    ? $requestedSlug
    : (array_key_first($profiles) ?? '');
$error = '';
$message = (isset($_GET['saved']) && $_GET['saved'] === '1') ? 'Teamprofil gespeichert.' : '';
$postString = static function (string $key): string {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $postString('csrf_token');
    $postSlug = $postString('profile_slug');
    if (!verifyCsrf($token)) {
        $error = 'Ungültige Anfrage. Bitte die Seite neu laden.';
    } elseif (!isset($profiles[$postSlug])) {
        $error = 'Unbekanntes Teamprofil.';
    } else {
        $data = [
            'display_name' => $postString('display_name'),
            'role_label' => $postString('role_label'),
            'bio' => $postString('bio'),
            'image_path' => $postString('image_path'),
            'is_visible' => isset($_POST['is_visible']) && $_POST['is_visible'] === '1',
        ];
        if (savePublicAuthorProfile($postSlug, $data)) {
            header('Location: ' . SITE_URL . '/admin/team.php?edit=' . rawurlencode($postSlug) . '&saved=1');
            exit;
        }
        $error = 'Das Profil konnte nicht gespeichert werden. Bitte Felder und Bildpfad prüfen.';
        $editSlug = $postSlug;
        $profiles[$postSlug] = array_merge($profiles[$postSlug], [
            'name' => $data['display_name'],
            'role' => $data['role_label'],
            'bio' => $data['bio'],
            'image_path' => $data['image_path'],
            'is_visible' => $data['is_visible'],
        ]);
    }
}

$profile = $profiles[$editSlug] ?? null;
$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Teamseiten – <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= h(SITE_URL) ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body class="admin-body">
<div class="admin-wrap">
<?php $adminActive = 'team'; require __DIR__ . '/partials/sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-header">
    <h1>Teamseiten</h1>
    <p style="color:var(--text-muted);font-size:.85rem">Öffentliche Angaben der Redaktion bearbeiten. Login-Daten bleiben davon getrennt.</p>
  </div>

  <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

  <div class="admin-profile-layout">
    <nav class="admin-card admin-profile-list" aria-label="Teamprofile">
      <h2>Redaktion</h2>
      <?php foreach ($profiles as $slug => $item): ?>
        <a href="?edit=<?= rawurlencode($slug) ?>" class="admin-profile-list-item<?= $slug === $editSlug ? ' active' : '' ?>">
          <span class="admin-profile-initials"><?= h($item['initials']) ?></span>
          <span><strong><?= h($item['name']) ?></strong><small><?= h($item['role']) ?> · <?= $item['is_visible'] ? 'sichtbar' : 'ausgeblendet' ?></small></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <?php if ($profile): ?>
    <section class="admin-card">
      <div class="admin-profile-form-header">
        <div>
          <h2><?= h($profile['name']) ?> bearbeiten</h2>
          <p>Öffentliche URL: /author.php?slug=<?= h($profile['slug']) ?></p>
        </div>
        <a class="btn btn-secondary" href="<?= h(SITE_URL) ?>/author.php?slug=<?= rawurlencode($profile['slug']) ?>" target="_blank">Seite ansehen</a>
      </div>

      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
        <input type="hidden" name="profile_slug" value="<?= h($profile['slug']) ?>">

        <div class="admin-profile-form-grid">
          <div class="form-group">
            <label for="display_name">Anzeigename *</label>
            <input class="form-control" type="text" id="display_name" name="display_name" maxlength="120" required value="<?= h($profile['name']) ?>">
          </div>
          <div class="form-group">
            <label for="role_label">Funktion *</label>
            <input class="form-control" type="text" id="role_label" name="role_label" maxlength="120" required value="<?= h($profile['role']) ?>" placeholder="z. B. Redaktion">
          </div>
        </div>

        <div class="form-group">
          <label for="image_path">Profilbild (lokaler Pfad)</label>
          <input class="form-control" type="text" id="image_path" name="image_path" maxlength="500" value="<?= h($profile['image_path']) ?>" placeholder="/assets/img/uploads/team/name.webp">
        </div>

        <div class="form-group">
          <label for="bio">Biografie</label>
          <textarea class="form-control" id="bio" name="bio" rows="10" maxlength="5000" placeholder="Kurze öffentliche Vorstellung"><?= h($profile['bio']) ?></textarea>
        </div>

        <label class="admin-profile-visible">
          <input type="checkbox" name="is_visible" value="1" <?= $profile['is_visible'] ? 'checked' : '' ?>>
          Profil öffentlich im Team anzeigen
        </label>

        <div class="admin-profile-actions">
          <button class="btn btn-primary" type="submit">Profil speichern</button>
          <a class="btn btn-secondary" href="<?= h(SITE_URL) ?>/team.php" target="_blank">Teamseite ansehen</a>
        </div>
      </form>
    </section>
    <?php endif; ?>
  </div>
</main>
</div>
</body>
</html>
