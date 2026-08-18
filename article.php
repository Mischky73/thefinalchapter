<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$requestedSlug = $_GET['slug'] ?? '';
$slug = is_string($requestedSlug) ? trim($requestedSlug) : '';
$article = $slug ? getArticleBySlug($slug) : null;

if (!$article) {
    http_response_code(404);
    $pageTitle  = '404 – Nicht gefunden | ' . SITE_NAME;
    $activePage = '';

    require_once __DIR__ . '/includes/partials/header.php';
    ?>
    <div class="container" style="padding: 4rem 0; text-align:center;">
      <div style="font-size:4rem;margin-bottom:1.5rem;opacity:.3">🤘</div>
      <h1 style="color:var(--accent);font-family:'Oswald',sans-serif;font-size:2.5rem">404</h1>
      <p style="color:var(--text-muted);margin:1rem 0 2rem">Dieser Beitrag wurde nicht gefunden.</p>
      <a href="<?= SITE_URL ?>" class="btn btn-primary">← Zur Startseite</a>
    </div>
    <?php
    require_once __DIR__ . '/includes/partials/footer.php';
    exit;
}

// Verwandte Artikel + Sidebar-Daten
$related        = getRelatedArticles((int)$article['category_id'], (int)$article['id'], 9);
$cats           = getCategoriesWithCount();
$latestArticles = getLatestArticlesSidebar(5);
$activePage     = $article['category_slug'] ?? '';
$authorSlug     = publicAuthorSlugForName((string)$article['author']);

$displayTitle = displayArticleTitle((string)$article['title']);
$pageTitle = h($displayTitle) . ' – ' . SITE_NAME;
$pageDescription = trim(strip_tags((string)($article['excerpt'] ?? '')));
if ($pageDescription === '') {
    $pageDescription = mb_substr(trim(strip_tags((string)$article['content'])), 0, 155);
}
$canonicalUrl = SITE_URL . '/article.php?slug=' . rawurlencode($article['slug']);
$pageImage = articleImage($article['featured_image'] ?? '');

require_once __DIR__ . '/includes/partials/header.php';
?>

<div class="container" style="padding-top:1.5rem">

  <!-- Breadcrumb -->
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= SITE_URL ?>">Startseite</a>
    <span class="breadcrumb-sep">›</span>
    <a href="<?= SITE_URL ?>/category.php?slug=<?= h($article['category_slug']) ?>"><?= h($article['category_name']) ?></a>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-current"><?= h(mb_substr($displayTitle, 0, 60)) ?><?= mb_strlen($displayTitle) > 60 ? '…' : '' ?></span>
  </nav>

  <div class="content-wrap content-wrap-with-left no-left-sidebar">
    

    <article class="article-full">

      <!-- Featured Image Hero mit lokalem Standardmotiv als Fallback -->
      <div class="article-hero">
        <img
          src="<?= h(articleImage($article['featured_image'])) ?>"
          alt="<?= h($displayTitle) ?>"
          class="article-hero-img<?= empty($article['featured_image']) ? ' is-fallback-image' : '' ?>"
          loading="eager"
          onerror="<?= h(articleImageErrorFallback()) ?>"
        >
        <div class="article-hero-overlay"></div>
        <div class="article-hero-content">
          <a href="<?= SITE_URL ?>/category.php?slug=<?= h($article['category_slug']) ?>"
             class="article-card-cat"><?= h($article['category_name']) ?></a>
          <h1 class="article-full-title"><?= h($displayTitle) ?></h1>
          <div class="article-full-meta">
            <span>von <?php if ($authorSlug !== null): ?><a href="<?= h(SITE_URL) ?>/author.php?slug=<?= rawurlencode($authorSlug) ?>"><?= h($article['author']) ?></a><?php else: ?><?= h($article['author']) ?><?php endif; ?></span>
            <span>·</span>
            <span><?= formatDate($article['created_at']) ?></span>
          </div>
          <?php if ($sourceLabel = articleSourceLabel($article)): ?>
            <p class="article-source-note article-source-note-hero">Quelle: <?= h($sourceLabel) ?></p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Artikel-Inhalt -->
      <div class="article-full-content">
        <?= sanitizeArticleHtml((string)$article['content']) ?>
      </div>

      <!-- Zurück-Link -->
      <a href="javascript:history.back()" class="btn-back">← Zurück</a>

      <!-- Verwandte Artikel -->
      <?php if (!empty($related)): ?>
      <div class="related-articles">
        <h2 class="section-title">Mehr aus dieser Kategorie</h2>
        <div class="related-grid">
          <?php foreach ($related as $r): ?>
          <article class="article-card" data-cat="<?= h($r['category_slug']) ?>">
            <div class="article-card-img-wrap">
              <a href="<?= SITE_URL ?>/article.php?slug=<?= h($r['slug']) ?>">
                <?php $relatedTitle = displayArticleTitle((string)$r['title']); ?>
                <img
                  src="<?= h(articleImage($r['featured_image'])) ?>"
                  alt="<?= h($relatedTitle) ?>"
                  class="article-card-img<?= empty($r['featured_image']) ? ' is-fallback-image' : '' ?>"
                  loading="lazy"
                  onerror="<?= h(articleImageErrorFallback()) ?>"
                >
              </a>
            </div>
            <div class="article-card-body">
              <span class="article-card-cat"><?= h($r['category_name']) ?></span>
              <h3 class="article-card-title">
                <a href="<?= SITE_URL ?>/article.php?slug=<?= h($r['slug']) ?>"><?= h($relatedTitle) ?></a>
              </h3>
              <div class="article-card-meta"><?= h($r['author']) ?> · <?= formatDate($r['created_at']) ?></div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </article>

    <!-- Sidebar -->
    <?php require_once __DIR__ . '/includes/partials/sidebar.php'; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/partials/footer.php'; ?>
