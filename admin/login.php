<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $token    = $_POST['csrf_token'] ?? '';

    if (!verifyCsrf($token)) {
        $error = 'Ungültige Anfrage. Bitte neu laden.';
    } elseif (login($username, $password)) {
        header('Location: ' . SITE_URL . '/admin/');
        exit;
    } else {
        $error = 'Benutzername oder Passwort falsch.';
    }
}

$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login – <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <div class="login-logo">The Final Chapter</div>
    <div class="login-sub">Redaktions-Login</div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
      <div class="form-group">
        <label for="username">Benutzername</label>
        <input type="text" id="username" name="username" class="form-control"
               value="<?= h($_POST['username'] ?? '') ?>" autocomplete="username" required>
      </div>
      <div class="form-group">
        <label for="password">Passwort</label>
        <input type="password" id="password" name="password" class="form-control"
               autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
        Einloggen
      </button>
    </form>

    <p style="text-align:center;margin-top:1rem;font-size:.8rem">
      <a href="<?= SITE_URL ?>">← Zur Website</a>
    </p>
  </div>
</div>
</body>
</html>
