<?php
// login.php - Primary Authentication Gateway
declare(strict_types=1);

// Temporarily enable during development to catch errors:
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once 'session.php';
require_once 'db.php'; // Person 2's database wrapper

$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        // Query user record using prepared statement to prevent SQL injection
        $stmt = $pdo->prepare("SELECT id, username, password_hash, mfa_enabled FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Constant-time password verification against Argon2id hash
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // Mitigate CWE-384: Regenerate session ID upon primary credential match
            session_regenerate_id(true);

            if (!empty($user['mfa_enabled'])) {
                // Set intermediate isolation state
                $_SESSION['mfa_pending_uid']  = (int)$user['id'];
                $_SESSION['mfa_pending_user'] = $user['username'];
                $_SESSION['authenticated']    = false;

                // Generate cryptographically secure 6-digit OTP
                $rawOtp = (string)random_int(100000, 999999);
                $hashedOtp = hash('sha256', $rawOtp);
                $expiryTimestamp = date('Y-m-d H:i:s', time() + 300); // 5-minute expiry

                // Add right after generating $rawOtp in login.php:
                $_SESSION['demo_otp'] = $rawOtp; // Temporary display token for local evaluation

                // Store hashed OTP in database
                $update = $pdo->prepare("UPDATE users SET mfa_code = ?, mfa_expiry = ? WHERE id = ?");
                $update->execute([$hashedOtp, $expiryTimestamp, $user['id']]);

                // For testing/demonstration purposes, display code or dispatch to MFA
                header("Location: mfa_verify.php");
                exit();
            }
        } else {
            // Generic error message suppresses account enumeration (CWE-209 / OWASP A07)
            $loginError = "Invalid username or password entered.";
        }
    } else {
        $loginError = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secured System - Sign In</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #f1f5f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #ffffff; padding: 32px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { margin-top: 0; color: #0f172a; text-align: center; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #334155; font-size: 14px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 15px; box-sizing: border-box; }
        input:focus { border-color: #2563eb; outline: none; }
        button { width: 100%; padding: 12px; background-color: #2563eb; color: #ffffff; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; }
        button:hover { background-color: #1d4ed8; }
        .error { background-color: #fee2e2; color: #991b1b; padding: 10px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; text-align: center; }
        .footer-link { display: block; text-align: center; margin-top: 16px; font-size: 13px; color: #64748b; text-decoration: none; }
        .footer-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <h2>System Login</h2>

        <?php if (!empty($loginError)): ?>
            <div class="error"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username" autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit">Sign In</button>
        </form>
        <a href="register.php" class="footer-link">Don't have an account? Register</a>
    </div>
</body>
</html>