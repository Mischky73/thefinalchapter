<?php
final class AuthSessionTestStatement {
    public function execute(array $params): bool {
        return $params === ['tester'];
    }

    public function fetch(): array {
        return [
            'id' => 42,
            'username' => 'tester',
            'password' => password_hash('correct-password', PASSWORD_DEFAULT),
            'role' => 'admin',
        ];
    }
}

final class AuthSessionTestDatabase {
    public function prepare(string $query): AuthSessionTestStatement {
        return new AuthSessionTestStatement();
    }
}

function getDB(): AuthSessionTestDatabase {
    return new AuthSessionTestDatabase();
}

require_once __DIR__ . '/../includes/auth.php';

$authSource = file_get_contents(__DIR__ . '/../includes/auth.php');
$verifyPosition = strpos($authSource, "password_verify(\$password, \$user['password'])");
$regeneratePosition = strpos($authSource, 'session_regenerate_id(true)');
$authDataPosition = strpos($authSource, "\$_SESSION['user_id']   =");
if ($verifyPosition === false || $regeneratePosition === false || $authDataPosition === false
    || !($verifyPosition < $regeneratePosition && $regeneratePosition < $authDataPosition)) {
    fwrite(STDERR, "FEHLER: Die Session muss nach der Passwortprüfung und vor dem Setzen der Auth-Daten mit Löschung der alten Session erneuert werden.\n");
    exit(1);
}
if (!preg_match('/if\s*\(\s*!session_regenerate_id\(true\)\s*\)\s*\{\s*return false;\s*\}/s', $authSource)) {
    fwrite(STDERR, "FEHLER: Eine fehlgeschlagene Session-Erneuerung muss den Login vor dem Setzen der Auth-Daten abbrechen.\n");
    exit(1);
}

$sessionDir = sys_get_temp_dir() . '/tfc-auth-session-test-' . bin2hex(random_bytes(6));
if (!mkdir($sessionDir, 0700, true) && !is_dir($sessionDir)) {
    fwrite(STDERR, "FEHLER: Temporäres Session-Verzeichnis konnte nicht erstellt werden.\n");
    exit(1);
}

session_save_path($sessionDir);
$fixedSessionId = 'fixed-session-id';
session_id($fixedSessionId);
sessionStart();
$_SESSION['pre_auth_marker'] = 'must-not-survive-under-old-id';
session_write_close();
$oldSessionFile = $sessionDir . '/sess_' . $fixedSessionId;
if (!is_file($oldSessionFile)) {
    fwrite(STDERR, "FEHLER: Alte Testsitzung wurde nicht angelegt.\n");
    exit(1);
}

try {
    if (!login('tester', 'correct-password')) {
        fwrite(STDERR, "FEHLER: Test-Login ist fehlgeschlagen.\n");
        exit(1);
    }

    if (session_id() === $fixedSessionId) {
        fwrite(STDERR, "FEHLER: Die Sitzungs-ID wurde nach erfolgreichem Login nicht erneuert.\n");
        exit(1);
    }

    clearstatcache(true, $oldSessionFile);
    if (is_file($oldSessionFile)) {
        fwrite(STDERR, "FEHLER: Die alte Sitzungsdatei wurde nach erfolgreichem Login nicht gelöscht.\n");
        exit(1);
    }

    if (($_SESSION['user_id'] ?? null) !== 42 || ($_SESSION['role'] ?? null) !== 'admin') {
        fwrite(STDERR, "FEHLER: Benutzerdaten wurden nach dem Login nicht korrekt gespeichert.\n");
        exit(1);
    }

    echo "OK: Erfolgreicher Login erneuert die Sitzungs-ID.\n";
} finally {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    foreach (glob($sessionDir . '/*') ?: [] as $file) {
        unlink($file);
    }
    rmdir($sessionDir);
}
