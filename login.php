<?php
// login.php - Primary Authentication Gateway
declare(strict_types=1);

require_once 'session.php';
require_once 'db.php'; 
require_once 'rate_limiter.php'; 

$loginError = '';
$limiter = new RateLimiter($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (!empty($username) && !empty($password)) {
        if ($limiter->isLockedOut($username)) {
            $loginError = "Akaun disekat sementara kerana terlalu banyak cubaan gagal. Sila cuba selepas 15 minit.";
        } else {
            $stmt = $pdo->prepare("SELECT id, username, password_hash, mfa_enabled FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $limiter->clearFailures($username);
                session_regenerate_id(true);

                if (!empty($user['mfa_enabled'])) {
                    $_SESSION['mfa_pending_uid']  = (int)$user['id'];
                    $_SESSION['mfa_pending_user'] = $user['username'];
                    $_SESSION['authenticated']    = false;

                    $rawOtp = (string)random_int(100000, 999999);
                    $hashedOtp = hash('sha256', $rawOtp);
                    $expiryTimestamp = date('Y-m-d H:i:s', time() + 300); 
                    $_SESSION['demo_otp'] = $rawOtp; 

                    $update = $pdo->prepare("UPDATE users SET mfa_code = ?, mfa_expiry = ? WHERE id = ?");
                    $update->execute([$hashedOtp, $expiryTimestamp, $user['id']]);

                    header("Location: mfa_verify.php");
                    exit();
                }
            } else {
                $limiter->recordFailure($username, $ip_address);
                $loginError = "Nama pengguna atau kata laluan tidak sah.";
            }
        }
    } else {
        $loginError = "Sila isi semua ruangan.";
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Log Masuk - Sistem Selamat</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #e0e7ff 0%, #f3e8ff 100%); display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #ffffff; padding: 40px; border-radius: 24px; box-shadow: 0 15px 35px rgba(99, 102, 241, 0.1); width: 100%; max-width: 400px; }
        h2 { margin-top: 0; color: #1e293b; text-align: center; font-weight: 600; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #475569; font-size: 14px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 14px 18px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 15px; box-sizing: border-box; font-family: 'Poppins', sans-serif; outline: none; transition: 0.3s; }
        input:focus { border-color: #818cf8; box-shadow: 0 0 0 4px rgba(129, 140, 248, 0.1); }
        button { width: 100%; padding: 15px; background: #818cf8; color: #ffffff; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; font-family: 'Poppins', sans-serif;}
        button:hover { background: #6366f1; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); }
        .error { background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; text-align: center; border: 1px solid #fca5a5; }
        .footer-link { display: block; text-align: center; margin-top: 25px; font-size: 14px; color: #64748b; text-decoration: none; }
        .footer-link:hover { color: #818cf8; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Log Masuk 🔒</h2>

        <?php if (!empty($loginError)): ?>
            <div class="error"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Nama Pengguna</label>
                <input type="text" id="username" name="username" required autocomplete="username" autofocus>
            </div>
            <div class="form-group">
                <label for="password">Kata Laluan</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit">Masuk</button>
        </form>
        <a href="register.php" class="footer-link">Belum ada akaun? Daftar sini</a>
    </div>
</body>
</html>