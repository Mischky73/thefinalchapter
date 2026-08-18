<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
$cat  = $slug ? getCategoryBySlug($slug) : null;

if (!$cat) {
    header('Location: ' . SITE_URL);
    exit;
}

// Kategorie-Startseiten: 1 großer + 2 mittlere + 30 kleine Beiträge = 33.
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 33;
$offset   = ($page - 1) * $perPage;
$total    = countArticlesByCategory((int)$cat['id']);
$pag      = paginate($total, $perPage, $page);
$articles = getArticlesByCategory((int)$cat['id'], $perPage, $offset);

$leadArticles = array_slice($articles, 0, 1);
$twoArticles  = array_slice($articles, 1, 2);
$gridArticles = array_slice($articles, 3, 30);

$cats           = getCategoriesWithCount();
$latestArticles = getLatestArticlesSidebar(5);
$activeCatSlug  = $slug;
$activePage     = $slug;
$pageTitle      = h($cat['name']) . ' – ' . SITE_NAME;
$pageDescription = trim(strip_tags((string)($cat['description'] ?? '')));
if ($pageDescription === '') {
    $pageDescription = 'Alle Beiträge aus der Rubrik ' . $cat['name'] . ' bei The Final Chapter: Metal-News, Reviews, Festivalberichte und Rückblicke.';
}
$canonicalUrl = SITE_URL . '/category.php?slug=' . rawurlencode($slug);

$renderCard = static function (array $article, string $variant = ''): void {
    $articleUrl = SITE_URL . '/article.php?slug=' . rawurlencode($article['slug']);
    $categoryUrl = SITE_URL . '/category.php?slug=' . rawurlencode($article['category_slug']);
    $featuredImage = trim((string)($article['featured_image'] ?? ''));
    $hasUsableFeaturedImage = $featuredImage !== '' && is_file(__DIR__ . '/' . ltrim($featuredImage, '/'));
    $classes = trim('article-card ' . $variant . (!$hasUsableFeaturedImage ? ' article-card-text-only' : ''));
    ?>
    <article class="<?= h($classes) ?>" data-cat="<?= h($article['category_slug']) ?>">
      <div class="article-card-img-wrap">
        <?php $displayTitle = displayArticleTitle((string)$article['title']); ?>
        <a href="<?= h($articleUrl) ?>" aria-label="<?= h($displayTitle) ?> lesen">
          <img src="<?= h(articleImage($featuredImage)) ?>"
               alt="<?= h($displayTitle) ?>"
               class="article-card-img<?= !$hasUsableFeaturedImage ? ' is-fallback-image' : '' ?>"
               loading="lazy"
               onerror="<?= h(articleImageErrorFallback()) ?>">
          <span class="sr-only"><?= h($displayTitle) ?> lesen</span>
        </a>
      </div>
      <div class="article-card-body">
        <a href="<?= h($categoryUrl) ?>" class="article-card-cat"><?= h($article['category_name']) ?></a>
        <h2 class="article-card-title">
          <a href="<?= h($articleUrl) ?>"><?= h($displayTitle) ?></a>
        </h2>
        <?php $displayExcerpt = displayArticleExcerpt($article['excerpt'] ?? '', 130); ?>
        <?php if ($displayExcerpt !== ''): ?>
          <p class="article-card-excerpt"><?= h($displayExcerpt) ?></p>
        <?php endif; ?>
        <?php if ($sourceLabel = articleSourceLabel($article)): ?>
          <p class="article-source-note">Quelle: <?= h($sourceLabel) ?></p>
        <?php endif; ?>
        <div class="article-card-meta">
          <?= h($article['author']) ?> · <?= formatDate($article['created_at']) ?>
        </div>
        <a href="<?= h($articleUrl) ?>" class="article-card-read-more">Weiterlesen<span class="sr-only">: <?= h($displayTitle) ?></span> →</a>
      </div>
    </article>
    <?php
};

require_once __DIR__ . '/includes/partials/header.php';
?>

<div class="container category-page">
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= SITE_URL ?>">Startseite</a>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-current"><?= h($cat['name']) ?></span>
  </nav>

  <div class="cat-header">
    <h1 class="cat-header-name"><?= h($cat['name']) ?></h1>
    <?php if ($cat['description']): ?>
      <p class="cat-header-desc"><?= h($cat['description']) ?></p>
    <?php endif; ?>
    <p class="cat-header-count"><?= $total ?> Beitrag<?= $total !== 1 ? 'e' : '' ?></p>
  </div>

  <div class="content-wrap category-content-wrap content-wrap-with-left no-left-sidebar">
    

    <main role="main" class="category-main">
      <?php if (empty($articles)): ?>
        <div class="category-empty">
          <p class="text-muted">Noch keine Beiträge in dieser Kategorie.</p>
          <a href="<?= SITE_URL ?>" class="btn btn-secondary">Zur Startseite</a>
        </div>
      <?php else: ?>

        <?php if (!empty($leadArticles)): $lead = $leadArticles[0]; ?>
          <section class="category-lead" aria-label="Neuester Beitrag">
            <article class="hero category-hero">
              <?php $leadTitle = displayArticleTitle((string)$lead['title']); ?>
              <img src="<?= h(articleImage($lead['featured_image'])) ?>"
                   alt="<?= h($leadTitle) ?>"
                   class="hero-img<?= empty($lead['featured_image']) ? ' is-fallback-image' : '' ?>"
                   onerror="<?= h(articleImageErrorFallback()) ?>">
              <div class="hero-content">
                <span class="hero-category"><?= h($lead['category_name']) ?></span>
                <h2 class="hero-title">
                  <a href="<?= SITE_URL ?>/article.php?slug=<?= rawurlencode($lead['slug']) ?>"><?= h($leadTitle) ?></a>
                </h2>
                <?php $leadExcerpt = displayArticleExcerpt($lead['excerpt'] ?? '', 190); ?>
                <?php if ($leadExcerpt !== ''): ?>
                  <p class="hero-excerpt"><?= h($leadExcerpt) ?></p>
                <?php endif; ?>
                <div class="hero-meta">
                  <span><?= h($lead['author']) ?></span>
                  <span><?= formatDate($lead['created_at']) ?></span>
                </div>
                <a href="<?= SITE_URL ?>/article.php?slug=<?= rawurlencode($lead['slug']) ?>" class="hero-read-more">Weiterlesen<span class="sr-only">: <?= h($leadTitle) ?></span> →</a>
              </div>
            </article>
          </section>
        <?php endif; ?>

        <?php if (!empty($twoArticles)): ?>
          <section class="category-grid category-grid-two category-top-grid" aria-label="Weitere aktuelle Beiträge">
            <?php foreach ($twoArticles as $index => $article) $renderCard($article, $index === 0 ? 'article-card-featured article-card-landscape' : 'article-card-featured article-card-portrait'); ?>
          </section>
        <?php endif; ?>

        <?php if (!empty($gridArticles)): ?>
          <section class="category-grid category-magazine-grid" aria-label="Weitere Beiträge">
            <?php foreach ($gridArticles as $index => $article): ?>
              <?php
                $variant = 'article-card-dense';
                if ($index % 9 === 0) {
                    $variant = 'article-card-horizontal';
                } elseif ($index % 9 === 4) {
                    $variant = 'article-card-horizontal article-card-reverse';
                } elseif ($index % 5 === 2) {
                    $variant .= ' article-card-portrait';
                } elseif ($index % 5 === 4) {
                    $variant .= ' article-card-landscape';
                }
                $renderCard($article, $variant);
              ?>
            <?php endforeach; ?>
          </section>
        <?php endif; ?>

        <?php if ($pag['pages'] > 1): ?>
          <nav class="pagination" aria-label="Seitennavigation">
            <?php if ($page > 1): ?>
              <a href="?slug=<?= h($slug) ?>&page=<?= $page - 1 ?>" class="prev-next" aria-label="Vorherige Seite">‹</a>
            <?php endif; ?>

            <?php if ($page > 3): ?>
              <a href="?slug=<?= h($slug) ?>&page=1">1</a>
              <?php if ($page > 4): ?><span class="dots">…</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($pag['pages'], $page + 2); $i++): ?>
              <?php if ($i === $page): ?>
                <span class="current" aria-current="page"><?= $i ?></span>
              <?php else: ?>
                <a href="?slug=<?= h($slug) ?>&page=<?= $i ?>"><?= $i ?></a>
              <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $pag['pages'] - 2): ?>
              <?php if ($page < $pag['pages'] - 3): ?><span class="dots">…</span><?php endif; ?>
              <a href="?slug=<?= h($slug) ?>&page=<?= $pag['pages'] ?>"><?= $pag['pages'] ?></a>
            <?php endif; ?>

            <?php if ($page < $pag['pages']): ?>
              <a href="?slug=<?= h($slug) ?>&page=<?= $page + 1 ?>" class="prev-next" aria-label="Nächste Seite">›</a>
            <?php endif; ?>
          </nav>
        <?php endif; ?>

      <?php endif; ?>
    </main>

    <?php require_once __DIR__ . '/includes/partials/sidebar.php'; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/partials/footer.php'; ?>
