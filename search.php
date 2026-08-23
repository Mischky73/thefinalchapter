<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$q      = normalizeSearchQuery((string)($_GET['q'] ?? ''));
$pageParam = $_GET['page'] ?? 1;
$page   = is_array($pageParam) ? 1 : max(1, (int)$pageParam);
$perPage = ARTICLES_PER_PAGE;
$offset = ($page - 1) * $perPage;

$results = [];
$total   = 0;
$pag     = null;
$searchTooShort = $q !== '' && !isSearchQueryLongEnough($q);

if ($q !== '' && !$searchTooShort) {
    $total   = countSearchResults($q);
    $pag     = paginate($total, $perPage, $page);
    $results = searchArticles($q, $perPage, $offset);
}

$cats           = getCategoriesWithCount();
$latestArticles = getLatestArticlesSidebar(5);

$pageTitle  = $q ? 'Suche: ' . h($q) . ' – ' . SITE_NAME : 'Suche – ' . SITE_NAME;
$activePage = 'search';

require_once __DIR__ . '/includes/partials/header.php';
?>

<div class="container" style="padding-top:1.5rem">

  <!-- Breadcrumb -->
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= SITE_URL ?>">Startseite</a>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-current">Suche<?= $q ? ': ' . h($q) : '' ?></span>
  </nav>

  <div class="content-wrap content-wrap-with-left no-left-sidebar">
    

    <main role="main">

      <!-- Suchformular -->
      <div class="search-header">
        <h1 class="section-title">🔍 Suche</h1>
        <form class="search-form-large" action="<?= SITE_URL ?>/search.php" method="get" role="search">
          <input
            type="search"
            name="q"
            class="search-input-large"
            placeholder="Band, Konzert, Album suchen…"
            value="<?= h($q) ?>"
            aria-label="Suchbegriff eingeben"
            autocomplete="off"
            autofocus
          >
          <button type="submit" class="search-submit-large">Suchen</button>
        </form>

        <?php if ($q !== '' && !$searchTooShort): ?>
        <p class="search-results-info">
          <?php if ($total > 0): ?>
            <strong><?= $total ?></strong> Ergebnis<?= $total !== 1 ? 'se' : '' ?> für
            „<strong><?= h($q) ?></strong>"
            <?php if ($pag && $pag['pages'] > 1): ?>
              – Seite <?= $page ?> von <?= $pag['pages'] ?>
            <?php endif; ?>
          <?php else: ?>
            Keine Ergebnisse für „<strong><?= h($q) ?></strong>"
          <?php endif; ?>
        </p>
        <?php endif; ?>
      </div>

      <!-- Suchergebnisse -->
      <?php if ($q === ''): ?>
        <div class="search-empty">
          <div class="search-empty-icon">🔍</div>
          <h2>Was suchst du?</h2>
          <p>Gib einen Suchbegriff ein, um Beiträge zu finden.</p>
        </div>

      <?php elseif ($searchTooShort): ?>
        <div class="search-empty">
          <div class="search-empty-icon">🔍</div>
          <h2>Suchbegriff zu kurz</h2>
          <p>Bitte gib mindestens zwei Zeichen ein.</p>
        </div>

      <?php elseif (empty($results)): ?>
        <div class="search-empty">
          <div class="search-empty-icon">😔</div>
          <h2>Keine Ergebnisse für „<?= h($q) ?>"</h2>
          <p>Versuche es mit einem anderen Suchbegriff oder stöbere in unseren Kategorien.</p>
          <div style="margin-top:1.5rem;display:flex;flex-wrap:wrap;gap:.75rem;justify-content:center">
            <?php foreach ($cats as $cat): ?>
              <a href="<?= SITE_URL ?>/category.php?slug=<?= h($cat['slug']) ?>"
                 class="btn btn-secondary btn-sm"><?= h($cat['name']) ?></a>
            <?php endforeach; ?>
          </div>
        </div>

      <?php else: ?>

      <div class="articles-grid">
        <?php foreach ($results as $a): ?>
        <article class="article-card" data-cat="<?= h($a['category_slug']) ?>">
          <div class="article-card-img-wrap">
            <a href="<?= SITE_URL ?>/article.php?slug=<?= h($a['slug']) ?>">
              <img
                src="<?= h(articleImage($a['featured_image'])) ?>"
                alt="<?= h($a['title']) ?>"
                class="article-card-img<?= empty($a['featured_image']) ? ' is-fallback-image' : '' ?>"
                loading="lazy"
                onerror="<?= h(articleImageErrorFallback()) ?>"
              >
            </a>
          </div>
          <div class="article-card-body">
            <a href="<?= SITE_URL ?>/category.php?slug=<?= h($a['category_slug']) ?>"
               class="article-card-cat"><?= h($a['category_name']) ?></a>
            <h3 class="article-card-title">
              <a href="<?= SITE_URL ?>/article.php?slug=<?= h($a['slug']) ?>"><?= h($a['title']) ?></a>
            </h3>
            <?php if ($a['excerpt']): ?>
              <p class="article-card-excerpt"><?= h(mb_substr($a['excerpt'], 0, 110)) ?>…</p>
            <?php endif; ?>
            <div class="article-card-meta">
              <?= h($a['author']) ?> · <?= formatDate($a['created_at']) ?>
            </div>
            <a href="<?= SITE_URL ?>/article.php?slug=<?= h($a['slug']) ?>" class="article-card-read-more">
              Weiterlesen →
            </a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>

      <!-- Paginierung -->
      <?php if ($pag && $pag['pages'] > 1): ?>
      <nav class="pagination" aria-label="Seitennavigation">
        <?php if ($page > 1): ?>
          <a href="?q=<?= urlencode($q) ?>&page=<?= $page - 1 ?>" class="prev-next" aria-label="Vorherige Seite">‹</a>
        <?php endif; ?>

        <?php if ($page > 3): ?>
          <a href="?q=<?= urlencode($q) ?>&page=1">1</a>
          <?php if ($page > 4): ?><span class="dots">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($pag['pages'], $page + 2); $i++): ?>
          <?php if ($i === $page): ?>
            <span class="current" aria-current="page"><?= $i ?></span>
          <?php else: ?>
            <a href="?q=<?= urlencode($q) ?>&page=<?= $i ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $pag['pages'] - 2): ?>
          <?php if ($page < $pag['pages'] - 3): ?><span class="dots">…</span><?php endif; ?>
          <a href="?q=<?= urlencode($q) ?>&page=<?= $pag['pages'] ?>"><?= $pag['pages'] ?></a>
        <?php endif; ?>

        <?php if ($page < $pag['pages']): ?>
          <a href="?q=<?= urlencode($q) ?>&page=<?= $page + 1 ?>" class="prev-next" aria-label="Nächste Seite">›</a>
        <?php endif; ?>
      </nav>
      <?php endif; ?>

      <?php endif; ?>
    </main>

    <!-- Sidebar -->
    <?php require_once __DIR__ . '/includes/partials/sidebar.php'; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/partials/footer.php'; ?>
