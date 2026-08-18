<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle  = 'Kontakt – ' . SITE_NAME;
$activePage = 'kontakt';
$latestArticles = getLatestArticlesSidebar(5);

require_once __DIR__ . '/includes/partials/header.php';
?>

<div class="container contact-page">
  <div class="content-wrap content-wrap-left-only no-left-sidebar">
    

    <main>

    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?= SITE_URL ?>">Startseite</a>
      <span class="breadcrumb-sep">›</span>
      <span class="breadcrumb-current">Kontakt</span>
    </nav>

    <h1>Kontakt</h1>
    <p class="text-muted" style="margin:.75rem 0 0">
      Du möchtest uns eine Rezensionsanfrage schicken, einen Livebericht einreichen
      oder einfach Hallo sagen? Meld dich!
    </p>

    <div class="contact-info-card">
      <div class="contact-info-item">
        <span class="contact-info-icon">📧</span>
        <div>
          <strong style="font-size:.8rem;font-family:'Oswald',sans-serif;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted)">E-Mail</strong><br>
          <a href="mailto:michajakob@t-online.de">michajakob@t-online.de</a>
        </div>
      </div>
      <div class="contact-info-item">
        <span class="contact-info-icon">📘</span>
        <div>
          <strong style="font-size:.8rem;font-family:'Oswald',sans-serif;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted)">Facebook</strong><br>
          <a href="<?= SITE_FACEBOOK ?>" target="_blank" rel="noopener noreferrer">facebook.com/TLCMagazin</a>
        </div>
      </div>
    </div>

    <div class="sidebar-widget" style="margin-top:2rem">
      <h3 class="sidebar-widget-title">Über das Magazin</h3>
      <p class="about-widget-text">
        <strong>The Final Chapter</strong> ist ein unabhängiges Heavy Metal Webmagazin aus Südthüringen.
        Wir berichten seit 2003 über Konzerte, Festivals und Alben aus der Metal-Welt –
        leidenschaftlich, unabhängig und authentisch.
      </p>
    </div>

    <p style="margin-top:2rem">
      <a href="<?= SITE_URL ?>" class="btn-back">← Zur Startseite</a>
    </p>

    </main>
  </div>
</div>

<?php require_once __DIR__ . '/includes/partials/footer.php'; ?>
