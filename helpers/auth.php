<?php
/**
 * Auth Helper - Session & Role Management
 */
require_once __DIR__ . '/../config/app.php';

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false,  // set true jika HTTPS
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function isLoggedIn(): bool {
    startSecureSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function isAdmin(): bool {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function isStaff(): bool {
    return isLoggedIn() && $_SESSION['role'] === 'staff';
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'        => $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'role'      => $_SESSION['role'],
        'full_name' => $_SESSION['full_name'] ?? '',
    ];
}

function loginUser(array $user): void {
    startSecureSession();
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['login_at']  = time();
    // Init CSRF token
    generateCsrfToken();
}

function logoutUser(): void {
    startSecureSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ── CSRF ─────────────────────────────────────────────────────────────────────

function generateCsrfToken(): string {
    startSecureSession();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCsrfToken(string $token): bool {
    startSecureSession();
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function csrfField(): string {
    $token = generateCsrfToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
}

// ── Redirect helpers ──────────────────────────────────────────────────────────

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        include __DIR__ . '/../views/errors/403.php';
        exit;
    }
}

function requireAdminOrStaff(): void {
    requireLogin();
    if (!isAdmin() && !isStaff()) {
        http_response_code(403);
        include __DIR__ . '/../views/errors/403.php';
        exit;
    }
}

function redirect(string $path): void {
    header('Location: ' . BASE_URL . $path);
    exit;
}

function redirectBack(string $fallback = '/'): void {
    $ref = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . $fallback);
    header('Location: ' . $ref);
    exit;
}
