<?php
// register.php - Registration Flow and Interface
declare(strict_types=1);

require_once 'session.php';
require_once 'db.php';   // Maintained by Person 2
require_once 'auth.php'; // Ingestion and Hashing functions

$error_message = '';
$success_message = '';

// Generate CSRF token for the registration form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process Registration Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = $_POST['csrf_token'] ?? '';
    
    // Anti-CSRF Token Validation
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        $error_message = "Invalid CSRF security token. Submission rejected.";
    } else {
        $username         = trim($_POST['username'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Semantic Check: Confirmation Match
        if ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } else {
            $result = register_user($pdo, $username, $email, $password);
            
            if ($result['success']) {
                // Regenerate CSRF token upon successful state modification
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header("Location: login.php?msg=registered_successfully");
                exit();
            } else {
                $error_message = $result['error'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure System - User Registration</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 360px; }
        h2 { margin-top: 0; color: #1e293b; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; color: #475569; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 10px; background: #2563eb; color: #fff; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn:hover { background: #1d4ed8; }
        .alert-error { background-color: #fee2e2; color: #991b1b; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; }
        .help-text { font-size: 11px; color: #64748b; margin-top: 4px; }
    </style>
</head>
<body>

<div class="card">
    <h2>Create an Account</h2>

    <?php if (!empty($error_message)): ?>
        <div class="alert-error"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form action="register.php" method="POST" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required maxlength="30" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="help-text">3-30 characters, alphanumeric and underscore only.</div>
        </div>

        <div class="form-group">
            <label for="email">Institutional Email</label>
            <input type="email" id="email" name="email" required maxlength="254" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required minlength="12" maxlength="128" autocomplete="new-password">
            <div class="help-text">Min 12 chars (must include upper, lower, number, symbol).</div>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="12" maxlength="128" autocomplete="new-password">
        </div>

        <button type="submit" class="btn">Register</button>
    </form>
</div>

</body>
</html>