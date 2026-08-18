<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Datenschutzerklärung';
$pageDescription = 'Datenschutzerklärung von The Final Chapter.press';
$latestArticles = getLatestArticlesSidebar(5);
include __DIR__ . '/includes/partials/header.php';
?>

<main class="site-main legal-page">
  <div class="container">
    <div class="content-wrap content-wrap-left-only no-left-sidebar no-left-sidebar legal-content-wrap">
      

      <div>
        <div class="page-header">
          <span class="section-kicker">Rechtliches</span>
          <h1>Datenschutzerklärung</h1>
          <div class="red-line"></div>
        </div>

        <div class="legal-content">
      <section class="legal-section" aria-labelledby="verantwortlicher-heading">
        <h2 id="verantwortlicher-heading">1. Verantwortlicher</h2>
        <p>
          Michael Jakob<br>
          The Final Chapter.press<br>
          Hoher Weg 29<br>
          96528 Frankenblick<br>
          Deutschland<br>
          E-Mail: <a href="mailto:michajakob@t-online.de">michajakob@t-online.de</a>
        </p>
      </section>

      <section class="legal-section" aria-labelledby="logfiles-heading">
        <h2 id="logfiles-heading">2. Aufruf der Website und Server-Logfiles</h2>
        <p>
          Beim Aufruf dieser Website werden technisch erforderliche Verbindungsdaten verarbeitet.
          Dazu können IP-Adresse, Datum und Uhrzeit des Zugriffs, aufgerufene Seite oder Datei,
          übertragene Datenmenge, Referrer-URL, Browsertyp, Betriebssystem und HTTP-Status gehören.
          Die Verarbeitung ist notwendig, um die Website auszuliefern, ihre Stabilität zu sichern
          und Angriffe oder technischen Missbrauch zu erkennen. Rechtsgrundlage ist Art. 6 Abs. 1
          lit. f DSGVO. Die Daten werden nur so lange gespeichert, wie dies für Betrieb,
          Fehleranalyse und Sicherheit erforderlich ist.
        </p>
      </section>

      <section class="legal-section" aria-labelledby="cloudflare-heading">
        <h2 id="cloudflare-heading">3. Cloudflare</h2>
        <p>
          Zur sicheren Bereitstellung der Website nutzen wir Dienste von Cloudflare. Cloudflare
          vermittelt die verschlüsselte Verbindung zwischen deinem Browser und unserem Server,
          schützt vor Angriffen und kann dabei insbesondere IP-Adresse, Anfrage- und
          Geräteinformationen verarbeiten. Anbieter ist Cloudflare, Inc., 101 Townsend St.,
          San Francisco, CA 94107, USA. Rechtsgrundlage ist unser berechtigtes Interesse an einer
          sicheren und zuverlässigen Website gemäß Art. 6 Abs. 1 lit. f DSGVO. Für Übermittlungen
          aus dem Europäischen Wirtschaftsraum in die USA beruft sich Cloudflare auf seine
          Zertifizierung nach dem EU-U.S. Data Privacy Framework. Sollte diese Grundlage nicht
          anwendbar sein, verwendet Cloudflare Standardvertragsklauseln einschließlich ergänzender
          Schutzmaßnahmen. Weitere Informationen und Hinweise zu den Garantien findest du in der
          <a href="https://www.cloudflare.com/privacypolicy/" target="_blank" rel="noopener noreferrer">Datenschutzerklärung von Cloudflare</a>.
        </p>
      </section>

      <section class="legal-section" aria-labelledby="fonts-heading">
        <h2 id="fonts-heading">4. Schriftarten</h2>
        <p>
          Die auf dieser Website verwendeten Schriftarten sind lokal eingebunden. Beim Laden der
          Seiten wird deshalb keine Verbindung zu einem externen Schriftartenanbieter hergestellt.
        </p>
      </section>

      <section class="legal-section" aria-labelledby="cookies-heading">
        <h2 id="cookies-heading">5. Cookies und Reichweitenmessung</h2>
        <p>
          Auf den öffentlichen Seiten verwenden wir keine Analyse- oder Werbe-Cookies und keine
          Reichweitenmessung. Beim Aufruf des geschützten Verwaltungsbereichs wird ein technisch
          notwendiges Sitzungs-Cookie verwendet, damit angemeldete Redakteure sicher arbeiten
          können. Cloudflare kann in sicherheitsrelevanten Ausnahmefällen technisch notwendige
          Cookies einsetzen. Für technisch notwendige Cookies ist keine Einwilligung erforderlich.
        </p>
      </section>

      <section class="legal-section" aria-labelledby="kontakt-datenschutz-heading">
        <h2 id="kontakt-datenschutz-heading">6. Kontaktaufnahme per E-Mail</h2>
        <p>
          Wenn du uns per E-Mail kontaktierst, verarbeiten wir deine Absenderadresse sowie die von
          dir übermittelten Angaben, um deine Anfrage zu beantworten. Dient die Nachricht der
          Anbahnung oder Durchführung eines Vertrags, ist Art. 6 Abs. 1 lit. b DSGVO die
          Rechtsgrundlage; in allen anderen Fällen Art. 6 Abs. 1 lit. f DSGVO. Die Daten werden
          gelöscht, sobald die Anfrage abschließend bearbeitet ist und keine gesetzlichen
          Aufbewahrungspflichten oder berechtigten Gründe für eine weitere Speicherung bestehen.
        </p>
      </section>

      <section class="legal-section" aria-labelledby="links-heading">
        <h2 id="links-heading">7. Externe Links und Facebook</h2>
        <p>
          Unsere Seiten enthalten Links zu externen Angeboten, darunter Facebook. Erst wenn du
          einen solchen Link öffnest, werden Daten an den jeweiligen Anbieter übertragen. Für die
          anschließende Verarbeitung gelten die Datenschutzbestimmungen des externen Anbieters.
          Auf dieser Website sind keine Facebook-Plugins oder Facebook-Trackingdienste eingebettet.
        </p>
      </section>

      <section class="legal-section" aria-labelledby="rechte-heading">
        <h2 id="rechte-heading">8. Deine Rechte</h2>
        <p>
          Du hast nach Maßgabe der gesetzlichen Voraussetzungen das Recht auf Auskunft (Art. 15
          DSGVO), Berichtigung (Art. 16 DSGVO), Löschung (Art. 17 DSGVO), Einschränkung der
          Verarbeitung (Art. 18 DSGVO), Datenübertragbarkeit (Art. 20 DSGVO) und Widerspruch gegen
          Verarbeitungen auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO (Art. 21 DSGVO). Außerdem
          kannst du dich gemäß Art. 77 DSGVO bei einer Datenschutzaufsichtsbehörde beschweren.
        </p>
      </section>

      <section class="legal-section" aria-labelledby="aufsicht-heading">
        <h2 id="aufsicht-heading">9. Datenschutzaufsicht</h2>
        <p>
          Zuständig ist der Thüringer Landesbeauftragte für den Datenschutz und die
          Informationsfreiheit, Häßlerstraße 8, 99096 Erfurt.<br>
          E-Mail: <a href="mailto:poststelle@datenschutz.thueringen.de">poststelle@datenschutz.thueringen.de</a><br>
          Website: <a href="https://tlfdi.de/" target="_blank" rel="noopener noreferrer">tlfdi.de</a>
        </p>
      </section>

      <section class="legal-section" aria-labelledby="sicherheit-heading">
        <h2 id="sicherheit-heading">10. Verschlüsselung</h2>
        <p>
          Diese Website wird verschlüsselt über HTTPS übertragen. Cloudflare nimmt die verschlüsselte
          Verbindung deines Browsers entgegen und übermittelt die Anfrage über eine separate,
          geschützte Verbindung beziehungsweise den Cloudflare Tunnel an unseren Server. Dadurch
          werden die übertragenen Inhalte auf dem Transportweg vor unbefugtem Mitlesen geschützt.
        </p>
      </section>

      <p class="legal-updated">Stand: Juli 2026</p>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>
