<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

function expectProfile(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FEHLER: {$message}\n");
        exit(1);
    }
}

$migration = __DIR__ . '/../migrations/20260719_add_author_profiles.sql';
$adminPage = __DIR__ . '/../admin/team.php';
expectProfile(is_file($migration), 'Die Migration für Autorenprofile fehlt.');
expectProfile(is_file($adminPage), 'Die Backend-Seite für Teamprofile fehlt.');
expectProfile(function_exists('getEditableAuthorProfiles'), 'Die Profilverwaltung kann keine Profile laden.');
expectProfile(function_exists('savePublicAuthorProfile'), 'Die Profilverwaltung kann keine Profile speichern.');
expectProfile(function_exists('isAllowedPublicProfileImage'), 'Die Bildpfad-Validierung fehlt.');

$migrationSource = file_get_contents($migration);
$adminSource = file_get_contents($adminPage);
expectProfile(str_contains($migrationSource, 'CREATE TABLE IF NOT EXISTS author_profiles'), 'Die Profil-Tabelle wird nicht sicher angelegt.');
expectProfile(str_contains($adminSource, 'requireLogin()'), 'Die Backend-Seite muss eine Anmeldung verlangen.');
expectProfile(str_contains($adminSource, 'verifyCsrf'), 'Profiländerungen benötigen CSRF-Schutz.');
expectProfile(str_contains($adminSource, 'csrf_token'), 'Das Profilformular benötigt ein CSRF-Feld.');
expectProfile(str_contains($adminSource, 'REQUEST_METHOD') && str_contains($adminSource, "=== 'POST'"), 'Profile dürfen nur per POST geändert werden.');
foreach (['display_name', 'role_label', 'bio', 'image_path', 'is_visible'] as $field) {
    expectProfile(str_contains($adminSource, 'name="' . $field . '"'), "Das editierbare Feld {$field} fehlt.");
}
expectProfile(!str_contains($adminSource, 'password'), 'Die Profilseite darf keine Passwörter verarbeiten.');
expectProfile(isAllowedPublicProfileImage(''), 'Ein leeres Profilbild muss erlaubt sein.');
expectProfile(isAllowedPublicProfileImage('/assets/img/uploads/team/michael.webp'), 'Lokale Asset-Pfade müssen erlaubt sein.');
expectProfile(!isAllowedPublicProfileImage('https://example.org/photo.webp'), 'Fremdgehostete Profilbilder müssen abgelehnt werden.');
expectProfile(!isAllowedPublicProfileImage('javascript:alert(1)'), 'Unsichere Bild-URLs müssen abgelehnt werden.');
expectProfile(!isAllowedPublicProfileImage('/assets/img/%2e%2e/admin/x.jpg'), 'Kodierte Pfadmanipulation muss abgelehnt werden.');
expectProfile(!isAllowedPublicProfileImage('/assets/img/%252e%252e/admin/x.jpg'), 'Mehrfach kodierte Pfadmanipulation muss abgelehnt werden.');

$db = getDB();
$profiles = getEditableAuthorProfiles();
$expectedSlugs = ['michael-jakob', 'thomas-schwarz', 'kay-herzer', 'matthias-eichhorn', 'alexander-goehring', 'heiko-mueller', 'enrico-reuter', 'jan-kullowatz', 'patricia-ferrantino'];
expectProfile(array_keys($profiles) === $expectedSlugs, 'Im Backend müssen alle freigegebenen Profile erscheinen.');

$db->beginTransaction();
try {
    $saved = savePublicAuthorProfile('michael-jakob', [
        'display_name' => 'Michael Test',
        'role_label' => 'Chefredaktion',
        'bio' => '<script>alert(1)</script> Testbiografie',
        'image_path' => '/assets/img/uploads/team/michael.webp',
        'is_visible' => false,
    ]);
    expectProfile($saved, 'Ein gültiges Profil konnte nicht gespeichert werden.');
    $hidden = getEditableAuthorProfiles()['michael-jakob'];
    expectProfile($hidden['name'] === 'Michael Test', 'Der Anzeigename wurde nicht gespeichert.');
    expectProfile($hidden['bio'] === '<script>alert(1)</script> Testbiografie', 'Die Biografie wurde nicht gespeichert.');
    expectProfile(!isset(getPublicTeamMembers()['michael-jakob']), 'Unsichtbare Profile dürfen öffentlich nicht erscheinen.');
    expectProfile(h($hidden['bio']) === '&lt;script&gt;alert(1)&lt;/script&gt; Testbiografie', 'Biografien müssen beim Rendern escaped werden.');
    expectProfile(!savePublicAuthorProfile('unbekannt', $hidden), 'Unbekannte Autorenprofile dürfen nicht gespeichert werden.');
    expectProfile(!savePublicAuthorProfile('michael-jakob', array_merge($hidden, ['image_path' => 'javascript:alert(1)'])), 'Unsichere Bildpfade dürfen nicht gespeichert werden.');
} finally {
    $db->rollBack();
}

fwrite(STDOUT, "OK: Editierbare öffentliche Autorenprofile sind sicher vom Login getrennt.\n");
