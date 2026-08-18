<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$db = getDB();
$msg = '';
$err = '';

// Benutzer löschen
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    // Eigenen Account nicht löschen
    if ($del_id !== (int)$_SESSION['user_id']) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$del_id]);
        $msg = 'Benutzer gelöscht.';
    } else {
        $err = 'Du kannst deinen eigenen Account nicht löschen.';
    }
}

// Benutzer speichern (neu oder bearbeiten)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id  = isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = in_array($_POST['role'] ?? '', ['admin','editor']) ? $_POST['role'] : 'editor';
    $password = trim($_POST['password'] ?? '');

    if ($username === '') {
        $err = 'Benutzername darf nicht leer sein.';
    } else {
        if ($edit_id > 0) {
            // Bearbeiten
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $db->prepare("UPDATE users SET username=?, email=?, role=?, password=? WHERE id=?")
                   ->execute([$username, $email, $role, $hash, $edit_id]);
            } else {
                $db->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?")
                   ->execute([$username, $email, $role, $edit_id]);
            }
            $msg = 'Benutzer aktualisiert.';
        } else {
            // Neu anlegen
            if ($password === '') {
                $err = 'Bitte ein Passwort vergeben.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                try {
                    $db->prepare("INSERT INTO users (username, email, role, password) VALUES (?,?,?,?)")
                       ->execute([$username, $email, $role, $hash]);
                    $msg = 'Benutzer angelegt.';
                } catch (PDOException $e) {
                    $err = 'Benutzername bereits vergeben.';
                }
            }
        }
    }
}

// Benutzer zum Bearbeiten laden
$edit_user = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Alle Benutzer laden
$users = $db->query("SELECT id, username, email, role, created_at FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Benutzerverwaltung – Admin</title>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-wrap">
<?php $adminActive = 'users'; require __DIR__ . '/partials/sidebar.php'; ?>
<main class="admin-main">
  <h1 class="admin-title">Benutzerverwaltung</h1>

  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <!-- Formular: Neu / Bearbeiten -->
  <div class="admin-card" style="margin-bottom:2rem">
    <h2 style="margin-top:0;font-size:1.1rem"><?= $edit_user ? 'Benutzer bearbeiten' : 'Neuen Benutzer anlegen' ?></h2>
    <form method="post" action="users.php<?= $edit_user ? '?edit='.$edit_user['id'] : '' ?>">
      <?php if ($edit_user): ?>
        <input type="hidden" name="edit_id" value="<?= $edit_user['id'] ?>">
      <?php endif; ?>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
        <div>
          <label style="display:block;margin-bottom:.3rem;color:var(--text-muted);font-size:.85rem">Benutzername</label>
          <input class="admin-input" type="text" name="username" required
                 value="<?= htmlspecialchars($edit_user['username'] ?? '') ?>"
                 placeholder="z.B. redaktion2">
        </div>
        <div>
          <label style="display:block;margin-bottom:.3rem;color:var(--text-muted);font-size:.85rem">E-Mail</label>
          <input class="admin-input" type="email" name="email"
                 value="<?= htmlspecialchars($edit_user['email'] ?? '') ?>"
                 placeholder="mail@example.com">
        </div>
        <div>
          <label style="display:block;margin-bottom:.3rem;color:var(--text-muted);font-size:.85rem">
            Passwort <?= $edit_user ? '<span style="color:var(--text-muted)">(leer lassen = unveraendert)</span>' : '' ?>
          </label>
          <input class="admin-input" type="password" name="password"
                 <?= $edit_user ? '' : 'required' ?>
                 placeholder="<?= $edit_user ? 'Leer lassen = unveraendert' : 'Passwort' ?>">
        </div>
        <div>
          <label style="display:block;margin-bottom:.3rem;color:var(--text-muted);font-size:.85rem">Rolle</label>
          <select class="admin-input" name="role">
            <option value="editor" <?= ($edit_user['role'] ?? '') === 'editor' ? 'selected' : '' ?>>Redakteur</option>
            <option value="admin"  <?= ($edit_user['role'] ?? '') === 'admin'  ? 'selected' : '' ?>>Admin</option>
          </select>
        </div>
      </div>
      <button class="btn btn-primary" type="submit">
        <?= $edit_user ? 'Speichern' : 'Benutzer anlegen' ?>
      </button>
      <?php if ($edit_user): ?>
        <a href="users.php" class="btn btn-secondary" style="margin-left:.5rem">Abbrechen</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Benutzerliste -->
  <table class="admin-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Benutzername</th>
        <th>E-Mail</th>
        <th>Rolle</th>
        <th>Erstellt</th>
        <th>Aktionen</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><?= $u['id'] ?></td>
        <td><?= htmlspecialchars($u['username']) ?></td>
        <td><?= htmlspecialchars($u['email'] ?? '–') ?></td>
        <td>
          <span style="color:<?= $u['role']==='admin' ? 'var(--accent)' : 'var(--text-muted)' ?>">
            <?= $u['role'] === 'admin' ? 'Admin' : 'Redakteur' ?>
          </span>
        </td>
        <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
        <td>
          <a href="users.php?edit=<?= $u['id'] ?>" class="btn btn-secondary" style="padding:.25rem .6rem;font-size:.8rem">Bearbeiten</a>
          <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
          <a href="users.php?delete=<?= $u['id'] ?>" class="btn btn-danger"
             style="padding:.25rem .6rem;font-size:.8rem;margin-left:.3rem"
             onclick="return confirm('Benutzer wirklich loeschen?')">Loeschen</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>
</div>
</body>
</html>
