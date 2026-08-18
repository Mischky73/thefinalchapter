<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$members = getPublicTeamMembers();
$latestArticles = getLatestArticlesSidebar(5);
$pageTitle = 'Team – ' . SITE_NAME;
$activePage = 'team';

require_once __DIR__ . '/includes/partials/header.php';
?>

<div class="container team-page">
  <div class="content-wrap content-wrap-left-only no-left-sidebar">
    

    <main class="team-page-main">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= h(SITE_URL) ?>">Startseite</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Team</span>
      </nav>

      <header class="team-page-header">
        <p class="team-kicker">The Final Chapter</p>
        <h1>Unser Team</h1>
        <p>Die Autoren hinter unserem unabhängigen Heavy-Metal-Webmagazin.</p>
      </header>

      <section class="team-grid" aria-label="Redaktion">
    <?php foreach ($members as $member): ?>
      <?php $articleCount = countPublishedArticlesByAuthors($member['article_authors']); ?>
      <article class="team-card">
        <a class="team-card-link" href="<?= h(SITE_URL) ?>/author.php?slug=<?= rawurlencode($member['slug']) ?>">
          <?php if ($member['image_path'] !== ''): ?>
            <img class="team-avatar team-photo" src="<?= h($member['image_path']) ?>" alt="<?= h($member['name']) ?>">
          <?php else: ?>
            <span class="team-avatar" aria-hidden="true"><?= h($member['initials']) ?></span>
          <?php endif; ?>
          <p class="team-role"><?= h($member['role']) ?></p>
          <h2><?= h($member['name']) ?></h2>
          <?php if (trim((string)$member['bio']) !== ''): ?>
            <p class="team-card-bio"><?= h(displayArticleExcerpt($member['bio'], 155)) ?></p>
          <?php endif; ?>
          <span class="team-article-count"><?= $articleCount ?> veröffentlichte Beiträge</span>
          <span class="team-profile-link">Autorenprofil ansehen →</span>
        </a>
      </article>
    <?php endforeach; ?>
      </section>
    </main>
  </div>
</div>

<?php require_once __DIR__ . '/includes/partials/footer.php'; ?>
