<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

function expectTeam(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FEHLER: {$message}\n");
        exit(1);
    }
}

$members = getPublicTeamMembers();
$expectedSlugs = ['michael-jakob', 'thomas-schwarz', 'kay-herzer', 'matthias-eichhorn', 'alexander-goehring', 'heiko-mueller', 'enrico-reuter', 'jan-kullowatz', 'patricia-ferrantino'];
expectTeam(array_keys($members) === $expectedSlugs,
    'Die öffentliche Teamliste muss alle bestätigten Autoren enthalten.');

foreach ($members as $slug => $member) {
    expectTeam(($member['slug'] ?? '') === $slug, 'Der Autoren-Slug ist inkonsistent.');
    expectTeam(trim((string)($member['name'] ?? '')) !== '', 'Der öffentliche Anzeigename fehlt.');
    expectTeam(($member['role'] ?? '') === 'Redaktion', 'Die öffentliche Rollenbezeichnung muss Redaktion sein.');
    expectTeam(!array_intersect(['email', 'username', 'password'], array_keys($member)),
        'Interne Kontodaten dürfen nicht im öffentlichen Profil stehen.');
    expectTeam(getPublicTeamMemberBySlug($slug) !== null, 'Ein bestätigter Autor muss per Slug auflösbar sein.');
}

expectTeam(getPublicTeamMemberBySlug('unbekannt') === null, 'Unbekannte Autoren dürfen kein Profil erhalten.');
expectTeam(publicAuthorSlugForName('Kai Herzer') === 'kay-herzer', 'Der vorhandene Namens-Tippfehler muss Kay zugeordnet werden.');
expectTeam(publicAuthorSlugForName('Metal Hammer') === null, 'Externe Quellen dürfen keine Teamprofile erhalten.');

foreach ($members as $member) {
    $count = countPublishedArticlesByAuthors($member['article_authors']);
    $articles = getPublishedArticlesByAuthors($member['article_authors'], 3, 0);
    expectTeam($count >= count($articles), 'Die Autorenanzahl ist kleiner als die Ergebnisliste.');
    expectTeam(count($articles) <= 3, 'Das Autorenlimit wird nicht eingehalten.');
    foreach ($articles as $article) {
        expectTeam(($article['status'] ?? '') === 'published', 'Autorenseiten dürfen nur veröffentlichte Artikel zeigen.');
        expectTeam(in_array($article['author'], $member['article_authors'], true), 'Ein fremder Artikel wurde zugeordnet.');
        expectTeam(isset($article['category_name'], $article['category_slug']), 'Kategoriedaten fehlen am Artikel.');
    }
}

$teamSource = file_get_contents(__DIR__ . '/../team.php');
$authorSource = file_get_contents(__DIR__ . '/../author.php');
$footerSource = file_get_contents(__DIR__ . '/../includes/partials/footer.php');
$articleSource = file_get_contents(__DIR__ . '/../article.php');
$css = file_get_contents(__DIR__ . '/../assets/css/style.css');

expectTeam(str_contains($teamSource, 'getPublicTeamMembers'), 'Die Teamübersicht nutzt nicht die öffentliche Teamliste.');
expectTeam(str_contains($authorSource, 'getPublicTeamMemberBySlug'), 'Die Autorenseite löst den öffentlichen Slug nicht auf.');
expectTeam(str_contains($authorSource, '$total > 0') && str_contains($authorSource, 'getPublishedArticlesByAuthors'),
    'Autorenseiten ohne Beiträge dürfen keine teure Artikellisten-Abfrage ausführen.');
expectTeam(str_contains($authorSource, 'http_response_code(404)'), 'Unbekannte Autoren benötigen HTTP 404.');
expectTeam(str_contains($footerSource, '/team.php') && str_contains($footerSource, '>Team<'),
    'Der Footer benötigt den Link Team.');
expectTeam(str_contains($articleSource, 'publicAuthorSlugForName'),
    'Autorenangaben in Artikeln sollen auf das öffentliche Profil verlinken.');
expectTeam(str_contains($css, '.team-page + .site-footer') && str_contains($css, '.author-page + .site-footer'),
    'Team- und Autorenseiten dürfen keinen hellen Abstand vor dem Footer erzeugen.');
expectTeam(!preg_match('/[📧📘⚡📊✏️📁🌐🚪🗑️]/u', $teamSource . $authorSource),
    'Team- und Autorenseiten dürfen keine UI-Emojis enthalten.');

fwrite(STDOUT, "OK: Öffentliche Team- und Autorenseiten sind sicher verknüpft.\n");
