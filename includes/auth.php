<?php
require_once __DIR__ . '/config.php';

function sessionStart(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

function isLoggedIn(): bool {
    sessionStart();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

// Alias – wird in manchen Admin-Seiten verwendet
function require_admin(): void {
    requireLogin();
}

function login(string $username, string $password): bool {
    sessionStart();
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT id, username, password, role FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            if (!session_regenerate_id(true)) {
                return false;
            }
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            return true;
        }
    } catch (Exception $e) {}
    return false;
}

function logout(): void {
    sessionStart();
    session_destroy();
}

function csrfToken(): string {
    sessionStart();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    sessionStart();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function currentUser(): array {
    sessionStart();
    return [
        'id'       => $_SESSION['user_id'] ?? 0,
        'username' => $_SESSION['username'] ?? '',
        'role'     => $_SESSION['role'] ?? '',
    ];
}
