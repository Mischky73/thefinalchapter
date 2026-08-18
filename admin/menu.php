<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$db  = getDB();
$msg = '';
$err = '';

// ── Aktionen ────────────────────────────────────────────────────────────────
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $label  = trim($_POST['label'] ?? '');
    $url    = trim($_POST['url'] ?? '');
    $parent = intval($_POST['parent_id'] ?? 0) ?: null;
    $target = ($_POST['target'] ?? '_self') === '_blank' ? '_blank' : '_self';
    if ($label && $url) {
        $maxStmt = $db->prepare("SELECT COALESCE(MAX(sort_order),0)+10 FROM nav_items WHERE parent_id <=> ?");
        $maxStmt->execute([$parent]);
        $max = (int)$maxStmt->fetchColumn();
        $db->prepare("INSERT INTO nav_items (label,url,parent_id,sort_order,target) VALUES (?,?,?,?,?)")
           ->execute([$label, $url, $parent, $max, $target]);
        $msg = "Eintrag \"$label\" hinzugefügt.";
    } else {
        $err = "Label und URL sind Pflichtfelder.";
    }
}

if ($action === 'delete' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    // Kinder auf null setzen
    $db->prepare("UPDATE nav_items SET parent_id = NULL WHERE parent_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM nav_items WHERE id = ?")->execute([$id]);
    $msg = "Eintrag gelöscht.";
}

if ($action === 'edit') {
    $id     = intval($_POST['id'] ?? 0);
    $label  = trim($_POST['label'] ?? '');
    $url    = trim($_POST['url'] ?? '');
    $parent = intval($_POST['parent_id'] ?? 0) ?: null;
    $target = ($_POST['target'] ?? '_self') === '_blank' ? '_blank' : '_self';
    $sort   = intval($_POST['sort_order'] ?? 0);
    if ($id && $label && $url) {
        $db->prepare("UPDATE nav_items SET label=?,url=?,parent_id=?,target=?,sort_order=? WHERE id=?")
           ->execute([$label, $url, $parent, $target, $sort, $id]);
        $msg = "Eintrag aktualisiert.";
    }
}

if ($action === 'move' && isset($_POST['id'], $_POST['dir'])) {
    $id  = intval($_POST['id']);
    $dir = $_POST['dir'];
    $current = $db->prepare("SELECT sort_order, parent_id FROM nav_items WHERE id=?");
    $current->execute([$id]);
    $currentRow = $current->fetch(PDO::FETCH_ASSOC);
    $curOrder = $currentRow['sort_order'] ?? null;
    $curParent = $currentRow['parent_id'] ?? null;
    if ($curOrder !== null && $dir === 'up') {
        $neighbor = $db->prepare("SELECT id, sort_order FROM nav_items WHERE parent_id <=> ? AND sort_order < ? ORDER BY sort_order DESC LIMIT 1");
    } elseif ($curOrder !== null && $dir === 'down') {
        $neighbor = $db->prepare("SELECT id, sort_order FROM nav_items WHERE parent_id <=> ? AND sort_order > ? ORDER BY sort_order ASC LIMIT 1");
    } else {
        $neighbor = null;
    }
    if ($neighbor) {
        $neighbor->execute([$curParent, $curOrder]);
        $n = $neighbor->fetch(PDO::FETCH_ASSOC);
    } else {
        $n = false;
    }
    if ($n) {
        $db->prepare("UPDATE nav_items SET sort_order=? WHERE id=?")->execute([$n['sort_order'], $id]);
        $db->prepare("UPDATE nav_items SET sort_order=? WHERE id=?")->execute([$curOrder, $n['id']]);
    }
}

// ── Daten laden ─────────────────────────────────────────────────────────────
$items = $db->query("SELECT * FROM nav_items ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
// Für Parent-Dropdown: nur Top-Level
$top_items = array_filter($items, fn($i) => $i['parent_id'] === null);

// Edit-Modus?
$edit_item = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM nav_items WHERE id=?");
    $s->execute([intval($_GET['edit'])]);
    $edit_item = $s->fetch(PDO::FETCH_ASSOC);
}

function renderItems(array $items, ?int $parent = null, int $depth = 0): void {
    foreach ($items as $item) {
        if ((int)($item['parent_id'] ?? 0) !== (int)$parent) continue;
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $depth);
        $arrow  = $depth > 0 ? '↳ ' : '';
        ?>
        <tr>
          <td><?= $indent . $arrow . h($item['label']) ?></td>
          <td style="color:var(--text-muted);font-size:.82rem"><?= h($item['url']) ?></td>
          <td><?= $item['target'] === '_blank' ? '↗ neues Tab' : '—' ?></td>
          <td>
            <form method="post" style="display:inline">
              <input type="hidden" name="action" value="move">
              <input type="hidden" name="id" value="<?= $item['id'] ?>">
              <button name="dir" value="up" title="Nach oben">▲</button>
              <button name="dir" value="down" title="Nach unten">▼</button>
            </form>
            <a href="?edit=<?= $item['id'] ?>" class="btn btn-secondary" style="font-size:.8rem;padding:.2rem .6rem">Bearbeiten</a>
            <form method="post" style="display:inline" onsubmit="return confirm('Wirklich löschen?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $item['id'] ?>">
              <button class="btn btn-danger" style="font-size:.8rem;padding:.2rem .6rem">Löschen</button>
            </form>
          </td>
        </tr>
        <?php
        // Kinder rekursiv
        renderItems($items, (int)$item['id'], $depth + 1);
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Menü-Verwaltung – <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-wrap">

  <?php $adminActive = 'menu'; require __DIR__ . '/partials/sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-header">
      <h1>Menü-Verwaltung</h1>
      <p style="color:var(--text-muted);font-size:.85rem">Hauptnavigation bearbeiten — Reihenfolge, Dropdowns, Links</p>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>

    <!-- Aktuelle Einträge -->
    <div class="admin-card" style="margin-bottom:2rem">
      <h2 style="font-size:1rem;margin-bottom:1rem">Aktuelle Menüeinträge</h2>
      <table class="admin-table">
        <thead><tr><th>Label</th><th>URL</th><th>Ziel</th><th>Aktionen</th></tr></thead>
        <tbody>
          <?php renderItems($items, null, 0); ?>
        </tbody>
      </table>
    </div>

    <!-- Formular: Neu oder Bearbeiten -->
    <div class="admin-card">
      <h2 style="font-size:1rem;margin-bottom:1rem">
        <?= $edit_item ? "Eintrag bearbeiten: \"" . h($edit_item['label']) . "\"" : "Neuen Eintrag hinzufügen" ?>
      </h2>
      <form method="post">
        <input type="hidden" name="action" value="<?= $edit_item ? 'edit' : 'add' ?>">
        <?php if ($edit_item): ?>
          <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
          <div>
            <label style="display:block;margin-bottom:.3rem;font-size:.85rem;color:var(--text-muted)">Label (Anzeigename) *</label>
            <input type="text" name="label" class="admin-input" required
                   value="<?= h($edit_item['label'] ?? '') ?>" placeholder="z.B. News">
          </div>
          <div>
            <label style="display:block;margin-bottom:.3rem;font-size:.85rem;color:var(--text-muted)">URL *</label>
            <input type="text" name="url" class="admin-input" required
                   value="<?= h($edit_item['url'] ?? '') ?>" placeholder="z.B. /category.php?slug=news">
          </div>
          <div>
            <label style="display:block;margin-bottom:.3rem;font-size:.85rem;color:var(--text-muted)">Untermenü von (optional)</label>
            <select name="parent_id" class="admin-input">
              <option value="0">— kein Elternelement (Top-Level) —</option>
              <?php foreach ($top_items as $ti): ?>
                <?php if ($edit_item && $ti['id'] == $edit_item['id']) continue; ?>
                <option value="<?= $ti['id'] ?>"
                  <?= ($edit_item && $edit_item['parent_id'] == $ti['id']) ? 'selected' : '' ?>>
                  <?= h($ti['label']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:.3rem;font-size:.85rem;color:var(--text-muted)">Link öffnen in</label>
            <select name="target" class="admin-input">
              <option value="_self" <?= ($edit_item && $edit_item['target'] === '_self') ? 'selected' : '' ?>>Gleichem Tab</option>
              <option value="_blank" <?= ($edit_item && $edit_item['target'] === '_blank') ? 'selected' : '' ?>>Neuem Tab</option>
            </select>
          </div>
          <?php if ($edit_item): ?>
          <div>
            <label style="display:block;margin-bottom:.3rem;font-size:.85rem;color:var(--text-muted)">Reihenfolge</label>
            <input type="number" name="sort_order" class="admin-input" value="<?= $edit_item['sort_order'] ?>">
          </div>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">
          <?= $edit_item ? 'Speichern' : 'Hinzufügen' ?>
        </button>
        <?php if ($edit_item): ?>
          <a href="menu.php" class="btn btn-secondary" style="margin-left:.5rem">Abbrechen</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="admin-card" style="margin-top:1.5rem;font-size:.83rem;color:var(--text-muted)">
      <strong>Tipp:</strong> Dropdown-Untermenüs erstellst du, indem du einen Eintrag als „Untermenü von" einem Top-Level-Eintrag zuordnest. Die Navigation auf der Website lädt die Einträge automatisch aus der Datenbank.
    </div>

  </main>
</div>
</body>
</html>
