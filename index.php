<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$perPage = 24;
$offset  = 0;

$db = getDB();
$usedArticleIds = [];

$fetchArticlesByWhere = static function (string $whereSql, array $params, int $limit, array $excludeIds = [], bool $newsOnly = false) use ($db): array {
    $excludeSql = '';
    if ($excludeIds) {
        $excludeSql = ' AND a.id NOT IN (' . implode(',', array_fill(0, count($excludeIds), '?')) . ')';
        $params = [...$params, ...array_values($excludeIds)];
    }

    $stmt = $db->prepare(
        "SELECT a.*, c.name AS category_name, c.slug AS category_slug
         FROM articles a
         JOIN categories c ON c.id = a.category_id
         LEFT JOIN categories parent ON parent.id = c.parent_id
         WHERE a.status = \"published\" AND ({$whereSql}){$excludeSql}
         ORDER BY a.created_at DESC, a.id DESC
         LIMIT ?"
    );
    $stmt->execute([...$params, $limit]);
    return $stmt->fetchAll();
};

$rememberArticles = static function (array $articles) use (&$usedArticleIds): array {
    foreach ($articles as $article) {
        $usedArticleIds[] = (int)$article['id'];
    }
    $usedArticleIds = array_values(array_unique($usedArticleIds));
    return $articles;
};

$articles = getLatestArticles($perPage, $offset);
$hero     = $articles[0] ?? null;
if ($hero) {
    $rememberArticles([$hero]);
}

$newsArticles = $rememberArticles($fetchArticlesByWhere('c.slug = ?', ['news'], 4, $usedArticleIds, true));
// Startseitenbereich „Acker, Bühne, Rückblick“: nur echte Berichte/Rückblicke.
// Schutzschicht: Nicht allein nach Kategorie filtern, weil Festival-News gelegentlich in
// Berichtskategorien landen können. Titelmuster halten News wie Early Bird, Anreise,
// Line-up oder Jubiläum aus dem Rückblick-Block heraus.
$reportWhere = <<<'SQL'
(c.slug = 'party-san' AND (a.title REGEXP '^Party[.]?San [0-9]{4}$' OR a.title REGEXP '^Party San [0-9]{4}$'))
OR (c.slug = 'sb' AND a.title REGEXP '^Summer Breeze [0-9]{4}$')
OR (c.slug = 'wff' AND a.title REGEXP '^With Full Force [0-9]{4}(:|$)')
OR (c.slug = 'in-flammen' AND a.title REGEXP '^In Flammen [0-9]{4}$')
OR (c.slug = 'wacken' AND a.title REGEXP '^Wacken( Open Air)? [0-9]{4}$')
OR (c.slug = 'rockharz' AND a.title REGEXP '^Rockharz [0-9]{4}$')
OR (c.slug = 'ragnaroeck' AND a.title REGEXP '^Rag[an]+roek [0-9]{4}$')
OR (c.slug = 'riedfest' AND a.title REGEXP '^Riedfest [0-9]{4}$')
OR (c.slug = 'wod' AND (a.title REGEXP '^WOD [0-9]{4}$' OR a.title REGEXP '^Way Of Darkness [0-9]{4}$'))
OR (c.slug = 'full-rewind' AND a.title REGEXP '^Full Rewind [0-9]{4}$')
SQL;
$reportWhere = "({$reportWhere}) AND NOT (
    a.title REGEXP ' 202[6-9]'
    AND LOWER(CONCAT(a.title, ' ', COALESCE(a.excerpt, ''))) REGEXP 'ausblick|vorschau|early.?bird|anreise|camping|line.?up|tickets|veröffentlicht|angekündigt|präsentiert'
)";
$festivalReports = $rememberArticles($fetchArticlesByWhere($reportWhere, [], 5, $usedArticleIds));
$featuredReport = $festivalReports[0] ?? null;
$reportCards = array_slice($festivalReports, 1, 4);

$festivalNews = [];
foreach (['party-san-news', 'wacken-news', 'full-rewind-news', 'summer-breeze-news', 'in-flammen-news', 'rockharz-news', 'wolfszeit-news'] as $festivalNewsSlug) {
    $items = $fetchArticlesByWhere('c.slug = ?', [$festivalNewsSlug], 1, $usedArticleIds, true);
    if ($items) {
        $festivalNews[] = $items[0];
        $rememberArticles($items);
    }
}
$reviewArticles = $rememberArticles($fetchArticlesByWhere('(c.slug = ? OR parent.slug = ?)', ['reviews', 'reviews'], 3, $usedArticleIds));
$latestMixed = $rememberArticles($fetchArticlesByWhere('1=1', [], 8, $usedArticleIds));

$pageTitle  = h(SITE_NAME) . ' – ' . h(SITE_TAGLINE);
$activePage = '';

$renderCard = static function (array $article, string $variant = ''): void {
    $articleUrl  = SITE_URL . '/article.php?slug=' . rawurlencode($article['slug']);
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
        <h3 class="article-card-title">
          <a href="<?= h($articleUrl) ?>"><?= h($displayTitle) ?></a>
        </h3>
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

$renderCompactItem = static function (array $article): void {
    $articleUrl  = SITE_URL . '/article.php?slug=' . rawurlencode($article['slug']);
    $categoryUrl = SITE_URL . '/category.php?slug=' . rawurlencode($article['category_slug']);
    $displayCategory = match ($article['category_slug']) {
        'party-san-news' => 'Party San',
        'wacken-news' => 'Wacken',
        'full-rewind-news' => 'Full Rewind',
        'summer-breeze-news' => 'Summer Breeze',
        'in-flammen-news' => 'In Flammen',
        'rockharz-news' => 'Rockharz',
        default => $article['category_name'],
    };
    $fallbackLogo = match ($article['category_slug']) {
        'party-san-news' => '/assets/img/festival-logos/party-san.webp',
        'wacken-news' => '/assets/img/festival-logos/wacken.webp',
        'full-rewind-news' => '/assets/img/festival-logos/full-rewind.webp',
        'summer-breeze-news' => '/assets/img/festival-logos/summer-breeze.webp',
        'in-flammen-news' => '/assets/img/festival-logos/in-flammen.webp',
        'rockharz-news' => '/assets/img/festival-logos/rockharz.webp',
        default => '',
    };
    $featuredImage = trim((string)($article['featured_image'] ?? ''));
    $hasUsableFeaturedImage = $featuredImage !== '' && is_file(__DIR__ . '/' . ltrim($featuredImage, '/'));
    $imageSrc = $hasUsableFeaturedImage ? articleImage($featuredImage) : ($fallbackLogo ? SITE_URL . $fallbackLogo : articleImage(''));
    ?>
    <article class="home-compact-item">
      <?php $displayTitle = displayArticleTitle((string)$article['title']); ?>
      <a href="<?= h($articleUrl) ?>" class="home-compact-media<?= !$hasUsableFeaturedImage && $fallbackLogo ? ' home-compact-logo' : '' ?>" aria-label="<?= h($displayTitle) ?> lesen">
        <img src="<?= h($imageSrc) ?>"
             alt="<?= h(!$hasUsableFeaturedImage && $fallbackLogo ? $displayCategory : $displayTitle) ?>"
             class="home-compact-img<?= !$hasUsableFeaturedImage ? ' is-fallback-image' : '' ?>"
             loading="lazy"
             onerror="<?= h(articleImageErrorFallback()) ?>">
        <span class="sr-only"><?= h($displayTitle) ?> lesen</span>
      </a>
      <div class="home-compact-body">
        <a href="<?= h($categoryUrl) ?>" class="home-compact-cat"><?= h($displayCategory) ?></a>
        <h3><a href="<?= h($articleUrl) ?>"><?= h($displayTitle) ?></a></h3>
        <div class="home-compact-meta"><?= h($article['author']) ?> · <?= formatDate($article['created_at']) ?></div>
      </div>
    </article>
    <?php
};

require_once __DIR__ . '/includes/partials/header.php';
?>

<div class="container home-page">
  <div class="content-wrap home-content-wrap no-left-sidebar">
    

    <main role="main" class="category-main home-main">
      <h1 class="section-title">Frisch aus dem Pit – Metal-News, Reviews und Festivalberichte</h1>

      <?php if (!$hero): ?>
        <div class="category-empty">
          <p class="text-muted">Noch keine Beiträge vorhanden.</p>
        </div>
      <?php else: ?>

        <section class="category-lead home-section" aria-label="Top-Beitrag">
          <article class="hero category-hero home-hero">
            <?php $heroTitle = displayArticleTitle((string)$hero['title']); ?>
            <img src="<?= h(articleImage($hero['featured_image'])) ?>"
                 alt="<?= h($heroTitle) ?>"
                 class="hero-img<?= empty($hero['featured_image']) ? ' is-fallback-image' : '' ?>"
                 loading="eager"
                 onerror="<?= h(articleImageErrorFallback()) ?>">
            <div class="hero-content">
              <a href="<?= SITE_URL ?>/category.php?slug=<?= rawurlencode($hero['category_slug']) ?>"
                 class="hero-category"><?= h($hero['category_name']) ?></a>
              <h2 class="hero-title">
                <a href="<?= SITE_URL ?>/article.php?slug=<?= rawurlencode($hero['slug']) ?>"><?= h($heroTitle) ?></a>
              </h2>
              <?php $heroExcerpt = displayArticleExcerpt($hero['excerpt'] ?? '', 190); ?>
              <?php if ($heroExcerpt !== ''): ?>
                <p class="hero-excerpt"><?= h($heroExcerpt) ?></p>
              <?php endif; ?>
              <?php if ($heroSource = articleSourceLabel($hero)): ?>
                <p class="hero-source-note">Quelle: <?= h($heroSource) ?></p>
              <?php endif; ?>
              <div class="hero-meta">
                <span><?= h($hero['author']) ?></span>
                <span><?= formatDate($hero['created_at']) ?></span>
              </div>
              <a href="<?= SITE_URL ?>/article.php?slug=<?= rawurlencode($hero['slug']) ?>" class="hero-read-more">Weiterlesen<span class="sr-only">: <?= h($heroTitle) ?></span> →</a>
            </div>
          </article>
        </section>

        <?php if ($newsArticles): ?>
          <section class="home-section" aria-labelledby="home-news-title">
            <div class="home-section-head">
              <h2 id="home-news-title">Top-News des Tages</h2>
              <a href="<?= SITE_URL ?>/category.php?slug=news">Alle News →</a>
            </div>
            <div class="category-grid category-grid-two home-grid-two">
              <?php foreach ($newsArticles as $index => $article) $renderCard($article, 'article-card-compact'); ?>
            </div>
          </section>
        <?php endif; ?>

        <?php if ($featuredReport || $reportCards): ?>
          <section class="home-section home-feature-section" aria-labelledby="home-reports-title">
            <div class="home-section-head">
              <h2 id="home-reports-title">Acker, Bühne, Rückblick</h2>
              <a href="<?= SITE_URL ?>/category.php?slug=festivals">Alle Stories →</a>
            </div>
            <div class="home-feature-grid">
              <?php if ($featuredReport) $renderCard($featuredReport, 'article-card-featured article-card-landscape'); ?>
              <?php if ($reportCards): ?>
                <div class="home-mini-grid">
                  <?php foreach ($reportCards as $index => $article) $renderCard($article, $index % 2 === 0 ? 'article-card-mini article-card-portrait' : 'article-card-mini article-card-landscape'); ?>
                </div>
              <?php endif; ?>
            </div>
          </section>
        <?php endif; ?>

        <?php if ($festivalNews): ?>
          <section class="home-section" aria-labelledby="home-festivalnews-title">
            <div class="home-section-head">
              <h2 id="home-festivalnews-title">Festival-News kompakt</h2>
              <a href="<?= SITE_URL ?>/category.php?slug=festival-news">Mehr vom Acker →</a>
            </div>
            <div class="home-compact-list">
              <?php foreach ($festivalNews as $article) $renderCompactItem($article); ?>
            </div>
          </section>
        <?php endif; ?>

        <?php if ($reviewArticles): ?>
          <section class="home-section" aria-labelledby="home-reviews-title">
            <div class="home-section-head">
              <h2 id="home-reviews-title">Auf dem Prüfstand</h2>
              <a href="<?= SITE_URL ?>/category.php?slug=reviews">Alle Urteile →</a>
            </div>
            <div class="category-grid category-grid-three home-grid-three">
              <?php foreach ($reviewArticles as $article) $renderCard($article, 'article-card-compact'); ?>
            </div>
          </section>
        <?php endif; ?>

        <?php if ($latestMixed): ?>
          <section class="home-section" aria-labelledby="home-latest-title">
            <div class="home-section-head">
              <h2 id="home-latest-title">Aus der Redaktion</h2>
              <span>kuratierter Mix statt Endlosfeed</span>
            </div>
            <div class="category-grid home-grid-four home-grid-compact">
              <?php foreach ($latestMixed as $index => $article): ?>
                <?php
                  $variant = 'article-card-dense';
                  if ($index % 8 === 0) {
                      $variant = 'article-card-horizontal';
                  } elseif ($index % 8 === 4) {
                      $variant = 'article-card-horizontal article-card-reverse';
                  } elseif ($index % 5 === 2) {
                      $variant .= ' article-card-portrait';
                  }
                  $renderCard($article, $variant);
                ?>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>

      <?php endif; ?>
    </main>

    <?php require_once __DIR__ . '/includes/partials/sidebar.php'; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/partials/footer.php'; ?>
