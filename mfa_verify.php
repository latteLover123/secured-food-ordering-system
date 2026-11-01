<?php
// mfa_verify.php - MFA Verification & Privilege Elevation Gateway
declare(strict_types=1);

require_once 'session.php';
require_once 'db.php'; // Person 2's database connection wrapper

// 1. STATE MACHINE GATEKEEPER: Ensure user passed primary authentication (login.php)
if (empty($_SESSION['mfa_pending_uid'])) {
    header("Location: login.php?msg=unauthorized_stage");
    exit();
}

$errorMessage = '';
$user_id = (int)$_SESSION['mfa_pending_uid'];

// 2. PROCESS MFA SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp'] ?? '');

    // Syntactical Validation: Must be strictly a 6-digit numerical string
    if (!preg_match('/^[0-9]{6}$/', $entered_otp)) {
        $errorMessage = "Invalid code format. Please enter a 6-digit numerical code.";
    } else {
        // Query OTP hash and expiration timestamp using prepared statements
        $stmt = $pdo->prepare("SELECT mfa_code, mfa_expiry, role, username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && !empty($user['mfa_code']) && !empty($user['mfa_expiry'])) {
            $currentTime = date('Y-m-d H:i:s');
            $hashedEnteredOtp = hash('sha256', $entered_otp);

            // Constant-time hash verification to neutralize timing attacks
            if (hash_equals($user['mfa_code'], $hashedEnteredOtp)) {
                
                // Semantic Validation: Check if the token has expired
                if ($currentTime <= $user['mfa_expiry']) {
                    
                    // --- SECURITY CONTROL 1: REPLAY ATTACK MITIGATION ---
                    // Immediately burn the token in the database so it cannot be reused
                    $burnStmt = $pdo->prepare("UPDATE users SET mfa_code = NULL, mfa_expiry = NULL WHERE id = ?");
                    $burnStmt->execute([$user_id]);

                    // --- SECURITY CONTROL 2: CWE-384 SESSION FIXATION DEFENSE ---
                    // Invalidate old intermediate session and issue a brand-new cryptographically secure ID
                    session_regenerate_id(true);

                    // --- SECURITY CONTROL 3: PRIVILEGE ELEVATION ---
                    // Clear intermediate flags and elevate to fully authorized state
                    unset($_SESSION['mfa_pending_uid']);
                    unset($_SESSION['mfa_pending_user']);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role']; // Consumed by Person 4 for RBAC
                    $_SESSION['authenticated'] = true;
                    $_SESSION['last_activity'] = time();

                    // Redirect to primary functional application module (Person 2's module)
                    header("Location: main_module.php");
                    exit();

                } else {
                    $errorMessage = "The verification code has expired. Please log in again.";
                }
            } else {
                $errorMessage = "Incorrect verification code. Access denied.";
            }
        } else {
            $errorMessage = "No active MFA session found. Please re-authenticate.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Verification - Secured System</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #f1f5f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #ffffff; padding: 32px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { margin-top: 0; color: #0f172a; text-align: center; }
        p { color: #475569; font-size: 14px; text-align: center; margin-bottom: 24px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #334155; font-size: 14px; }
        input[type="text"] { width: 100%; padding: 12px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 18px; letter-spacing: 4px; text-align: center; box-sizing: border-box; }
        input[type="text"]:focus { border-color: #2563eb; outline: none; }
        button { width: 100%; padding: 12px; background-color: #2563eb; color: #ffffff; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; }
        button:hover { background-color: #1d4ed8; }
        .error { background-color: #fee2e2; color: #991b1b; padding: 10px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; text-align: center; }
        .cancel-link { display: block; text-align: center; margin-top: 16px; font-size: 13px; color: #64748b; text-decoration: none; }
        .cancel-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Two-Factor Challenge</h2>
        <p>A verification code has been generated for your account. Enter the 6-digit code below to finalize login.</p>

        <?php if (!empty($errorMessage)): ?>
            <div class="error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['demo_otp'])): ?>
    <div style="background:#e0f2fe; color:#0369a1; padding:10px; border-radius:6px; margin-bottom:15px; font-size:13px; text-align:center;">
        <strong>[DEMO / TESTING OTP]:</strong> <?= htmlspecialchars($_SESSION['demo_otp'], ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

        <form method="POST" action="mfa_verify.php">
            <div class="form-group">
                <label for="otp">Enter 6-Digit Code</label>
                <input type="text" id="otp" name="otp" maxlength="6" pattern="[0-9]{6}" required autocomplete="one-time-code" placeholder="123456" autofocus>
            </div>
            <button type="submit">Verify Identity</button>
        </form>
        <a href="login.php" class="cancel-link">Back to Login</a>
    </div>
</body>
</html>