<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$requestedSlug = $_GET['slug'] ?? '';
$slug = is_string($requestedSlug) ? trim($requestedSlug) : '';
$member = $slug !== '' ? getPublicTeamMemberBySlug($slug) : null;

if ($member === null) {
    http_response_code(404);
    $pageTitle = 'Autor nicht gefunden – ' . SITE_NAME;
    $activePage = 'team';
    require_once __DIR__ . '/includes/partials/header.php';
    ?>
    <main class="container team-page team-not-found">
      <h1>Autor nicht gefunden</h1>
      <p>Dieses Autorenprofil ist nicht verfügbar.</p>
      <a href="<?= h(SITE_URL) ?>/team.php" class="btn btn-primary">Zum Team</a>
    </main>
    <?php
    require_once __DIR__ . '/includes/partials/footer.php';
    exit;
}

$requestedPage = $_GET['page'] ?? 1;
$page = is_string($requestedPage) || is_int($requestedPage)
    ? max(1, normalizeNonNegativeIntInput($requestedPage, 1))
    : 1;
$perPage = 24;
$total = countPublishedArticlesByAuthors($member['article_authors']);
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;
$articles = $total > 0
    ? getPublishedArticlesByAuthors($member['article_authors'], $perPage, $offset)
    : [];
$latestArticles = getLatestArticlesSidebar(5);

$pageTitle = $member['name'] . ' – Team – ' . SITE_NAME;
$activePage = 'team';
require_once __DIR__ . '/includes/partials/header.php';
?>

<div class="container author-page">
  <div class="content-wrap content-wrap-left-only no-left-sidebar">
    

    <main class="author-page-main">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= h(SITE_URL) ?>">Startseite</a>
        <span class="breadcrumb-sep">›</span>
        <a href="<?= h(SITE_URL) ?>/team.php">Team</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current"><?= h($member['name']) ?></span>
      </nav>

      <header class="author-header">
    <?php if ($member['image_path'] !== ''): ?>
      <img class="team-avatar author-avatar team-photo" src="<?= h($member['image_path']) ?>" alt="<?= h($member['name']) ?>">
    <?php else: ?>
      <span class="team-avatar author-avatar" aria-hidden="true"><?= h($member['initials']) ?></span>
    <?php endif; ?>
    <div>
      <p class="team-role"><?= h($member['role']) ?></p>
      <h1><?= h($member['name']) ?></h1>
      <p><?= $total ?> veröffentlichte Beiträge bei The Final Chapter</p>
      <?php if ($member['bio'] !== ''): ?>
        <div class="author-bio"><?= nl2br(h($member['bio'])) ?></div>
      <?php endif; ?>
    </div>
      </header>

      <section class="author-articles" aria-labelledby="author-articles-title">
    <h2 id="author-articles-title" class="section-title">Beiträge von <?= h($member['name']) ?></h2>

    <?php if ($articles === []): ?>
      <p class="team-empty">Noch keine veröffentlichten Beiträge.</p>
    <?php else: ?>
      <div class="author-articles-grid category-grid-three">
        <?php foreach ($articles as $article): ?>
          <?php $articleUrl = SITE_URL . '/article.php?slug=' . rawurlencode($article['slug']); ?>
          <article class="article-card" data-cat="<?= h($article['category_slug']) ?>">
            <div class="article-card-img-wrap">
              <a href="<?= h($articleUrl) ?>">
                <?php $displayTitle = displayArticleTitle((string)$article['title']); ?>
                <img src="<?= h(articleImage($article['featured_image'])) ?>"
                     alt="<?= h($displayTitle) ?>"
                     class="article-card-img<?= empty($article['featured_image']) ? ' is-fallback-image' : '' ?>"
                     loading="lazy"
                     onerror="<?= h(articleImageErrorFallback()) ?>">
              </a>
            </div>
            <div class="article-card-body">
              <span class="article-card-cat"><?= h($article['category_name']) ?></span>
              <h3 class="article-card-title"><a href="<?= h($articleUrl) ?>"><?= h($displayTitle) ?></a></h3>
              <?php $displayExcerpt = displayArticleExcerpt($article['excerpt'] ?? '', 115); ?>
              <?php if ($displayExcerpt !== ''): ?><p class="article-card-excerpt"><?= h($displayExcerpt) ?></p><?php endif; ?>
              <div class="article-card-meta"><?= formatDate($article['created_at']) ?></div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($pages > 1): ?>
      <nav class="pagination" aria-label="Seitennavigation">
        <?php if ($page > 1): ?>
          <a class="prev-next" href="?slug=<?= rawurlencode($slug) ?>&page=<?= $page - 1 ?>" aria-label="Vorherige Seite">‹</a>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
          <?php if ($i === $page): ?>
            <span class="current" aria-current="page"><?= $i ?></span>
          <?php else: ?>
            <a href="?slug=<?= rawurlencode($slug) ?>&page=<?= $i ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $pages): ?>
          <a class="prev-next" href="?slug=<?= rawurlencode($slug) ?>&page=<?= $page + 1 ?>" aria-label="Nächste Seite">›</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
      </section>
    </main>
  </div>
</div>

<?php require_once __DIR__ . '/includes/partials/footer.php'; ?>
