<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Impressum';
$pageDescription = 'Impressum und Anbieterkennzeichnung von The Final Chapter.press';
$latestArticles = getLatestArticlesSidebar(5);
include __DIR__ . '/includes/partials/header.php';
?>

<main class="site-main legal-page">
  <div class="container">
    <div class="content-wrap content-wrap-left-only no-left-sidebar no-left-sidebar legal-content-wrap">
      

      <div>
        <div class="page-header">
          <span class="section-kicker">Rechtliches</span>
          <h1>Impressum</h1>
          <div class="red-line"></div>
        </div>

        <div class="legal-content">
      <section class="legal-section" aria-labelledby="anbieter-heading">
        <h2 id="anbieter-heading">Angaben gemäß § 5 DDG</h2>
        <p>
          <strong>The Final Chapter.press</strong><br>
          Diensteanbieter und Inhaber: Michael Jakob<br>
          Hoher Weg 29<br>
          96528 Frankenblick<br>
          Deutschland
        </p>
      </section>

      <section class="legal-section" aria-labelledby="kontakt-heading">
        <h2 id="kontakt-heading">Kontakt</h2>
        <p>
          E-Mail:
          <a href="mailto:michajakob@t-online.de">michajakob@t-online.de</a>
        </p>
      </section>

      <section class="legal-section" aria-labelledby="redaktion-heading">
        <h2 id="redaktion-heading">Redaktionell verantwortlich</h2>
        <p>
          Verantwortlich gemäß § 18 Abs. 2 MStV:<br>
          Michael Jakob<br>
          Hoher Weg 29<br>
          96528 Frankenblick<br>
          Deutschland
        </p>
      </section>

      <section class="legal-section" aria-labelledby="hinweis-heading">
        <h2 id="hinweis-heading">Hinweis zu externen Links</h2>
        <p>
          Für Inhalte verlinkter externer Internetseiten sind ausschließlich deren jeweilige
          Betreiber verantwortlich. Rechtswidrige Inhalte werden nach Kenntniserlangung geprüft
          und entsprechende Links gegebenenfalls entfernt.
        </p>
      </section>

      <p class="legal-updated">Stand: Juli 2026</p>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>
