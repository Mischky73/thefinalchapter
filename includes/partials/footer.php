<?php
$currentYear = date('Y');
$siteUrl = htmlspecialchars(rtrim((string) SITE_URL, '/'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<footer class="site-footer">
  <div class="container">
    <div class="footer-cols">
      <div class="footer-col footer-brand">
        <a href="<?= $siteUrl ?>" class="footer-logo">The Final Chapter</a>
        <p>Unabhängiges Heavy-Metal-Webmagazin aus Südthüringen.</p>
      </div>

      <div class="footer-col">
        <h4>Magazin</h4>
        <a href="<?= $siteUrl ?>/team.php">Team</a>
        <a href="<?= $siteUrl ?>/kontakt.php">Kontaktseite</a>
      </div>

      <div class="footer-col">
        <h4>Kontakt</h4>
        <a href="<?= $siteUrl ?>/kontakt.php">E-Mail über die Kontaktseite</a>
      </div>

      <div class="footer-col">
        <h4>Rechtliches</h4>
        <a href="<?= $siteUrl ?>/impressum.php">Impressum</a>
        <a href="<?= $siteUrl ?>/datenschutz.php">Datenschutz</a>
      </div>
    </div>

    <div class="footer-bottom">
      <span>© <?= $currentYear ?> The Final Chapter</span>
      <span>Heavy Metal aus Südthüringen</span>
    </div>
  </div>
</footer>
<script src="<?= $siteUrl ?>/assets/js/main.js"></script>
</body>
</html>
