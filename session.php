<?php
// session.php - Centralized Secure Session Engine
declare(strict_types=1);

$idle_timeout = 900; // 15-minute inactivity timeout

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_set_cookie_params([
    'lifetime' => 0,              // Expires when browser closes
    'path'     => '/',
    'domain'   => '',
    'secure'   => true,           // Enforced over HTTPS
    'httponly' => true,           // Inaccessible to JavaScript (XSS defense)
    'samesite' => 'Strict'        // Mitigates CSRF
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inactivity Timeout Verification (CWE-613 Mitigation)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $idle_timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php?msg=session_expired");
    exit();
}
$_SESSION['last_activity'] = time();

// Access Control Gatekeeper Helper
function enforce_authenticated_session(): void {
    if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        session_unset();
        session_destroy();
        header("Location: login.php?msg=unauthorized");
        exit();
    }
}
function requireAuthentication(): void {
    enforce_authenticated_session();
}