<?php
// session.php
declare(strict_types=1);

$idle_timeout = 900; 

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_set_cookie_params([
    'lifetime' => 0,              
    'path'     => '/',
    'domain'   => '',
    'secure'   => false, // Tukar ke false untuk localhost WAMP
    'httponly' => true,           
    'samesite' => 'Strict'        
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $idle_timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php?msg=session_expired");
    exit();
}
$_SESSION['last_activity'] = time();

function enforce_authenticated_session(): void {
    if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        session_unset();
        session_destroy();
        header("Location: login.php?msg=unauthorized");
        exit();
    }
}
?>