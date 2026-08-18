<?php
// Wiederverwendbare Festival-News-Sidebar für alle Frontend-Seiten
$festivalNewsNow = $festivalNewsNow ?? new DateTimeImmutable('now', new DateTimeZone('Europe/Berlin'));
$festivalNewsGroups = getFestivalNewsSidebarGroups(5, $festivalNewsNow);
?>
<aside class="sidebar" role="complementary">

  <div class="sidebar-widget festival-news-widget">
    <h3 class="sidebar-widget-title">
      <a href="<?= SITE_URL ?>/category.php?slug=festival-news">Festival-News</a>
    </h3>

    <?php if ($festivalNewsGroups): ?>
      <?php foreach ($festivalNewsGroups as $festivalGroup): ?>
      <details class="festival-news-group" data-festival-slug="<?= h($festivalGroup['slug']) ?>"<?= in_array($festivalGroup['slug'], ['party-san-news', 'wacken-news'], true) ? ' open' : '' ?>>
        <summary class="festival-news-group-title">
          <span class="festival-news-heading">
            <?php if (!empty($festivalGroup['logo'])): ?>
              <img src="<?= h($festivalGroup['logo']) ?>"
                   alt="<?= h(($festivalGroup['display_name'] ?? $festivalGroup['name']) . ' Festival-News') ?>"
                   class="festival-news-logo"
                   width="56"
                   height="36"
                   loading="lazy">
            <?php endif; ?>
            <span class="festival-news-heading-text">
              <span class="festival-news-subcategory"><?= h($festivalGroup['display_name'] ?? $festivalGroup['name']) ?></span>
              <span class="festival-news-date">
                <?php if (!empty($festivalGroup['start_date'])): ?>
                  <time datetime="<?= h((string)$festivalGroup['start_date']) ?>"><?= h((string)$festivalGroup['date_label']) ?></time>
                <?php else: ?>
                  <span><?= h((string)$festivalGroup['date_label']) ?></span>
                <?php endif; ?>
                <span aria-hidden="true"> · </span><?= h((string)$festivalGroup['countdown_label']) ?>
              </span>
            </span>
          </span>
        </summary>

        <div class="festival-news-group-content">
        <?php if ($festivalGroup['articles']): ?>
          <?php foreach ($festivalGroup['articles'] as $festivalArticle): ?>
          <div class="sidebar-article">
            <a href="<?= SITE_URL ?>/article.php?slug=<?= h($festivalArticle['slug']) ?>" class="sidebar-article-media" aria-label="<?= h($festivalArticle['title']) ?> lesen">
              <img src="<?= h(articleImage($festivalArticle['featured_image'])) ?>"
                   alt="<?= h($festivalArticle['title']) ?>"
                   class="sidebar-article-img<?= empty($festivalArticle['featured_image']) ? ' is-fallback-image' : '' ?>"
                   loading="lazy"
                   onerror="<?= h(articleImageErrorFallback()) ?>">
              <span class="sr-only"><?= h($festivalArticle['title']) ?> lesen</span>
            </a>
            <div>
              <div class="sidebar-article-title">
                <?php if (isFestivalArticleNew($festivalArticle['created_at'], $festivalNewsNow)): ?>
                  <span class="festival-news-new">NEU</span>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>/article.php?slug=<?= h($festivalArticle['slug']) ?>"><?= h($festivalArticle['title']) ?></a>
              </div>
              <div class="sidebar-article-meta"><?= formatDate($festivalArticle['created_at']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted festival-news-empty">Noch keine Meldungen veröffentlicht.</p>
        <?php endif; ?>
          <a href="<?= SITE_URL ?>/category.php?slug=<?= h($festivalGroup['slug']) ?>" class="festival-news-all-link">Alle <?= h($festivalGroup['display_name'] ?? $festivalGroup['name']) ?> News →</a>
        </div>
      </details>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-muted festival-news-empty">Noch keine Festival-News veröffentlicht.</p>
    <?php endif; ?>

    <a href="<?= SITE_URL ?>/category.php?slug=festival-news" class="article-card-read-more">Alle Festival-News →</a>
  </div>

</aside>
