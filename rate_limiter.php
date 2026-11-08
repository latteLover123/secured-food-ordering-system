<?php
// rate_limiter.php
// Tugas: Mitigate Brute-Force (SR1) & Account Takeover (AC1)

class RateLimiter {
    private $pdo;
    private $max_attempts = 5;
    private $lockout_minutes = 15;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Rekod cubaan login yang gagal
    public function recordFailure($username, $ip) {
        $stmt = $this->pdo->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, 0)");
        $stmt->execute([$username, $ip]);
    }

    // Padam rekod gagal bila login berjaya
    public function clearFailures($username) {
        $stmt = $this->pdo->prepare("DELETE FROM login_attempts WHERE username = ? AND success = 0");
        $stmt->execute([$username]);
    }

    // Semak sama ada akaun/IP sedang di-lock
    public function isLockedOut($username) {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as failed_count FROM login_attempts 
             WHERE username = ? AND success = 0 
             AND attempt_time > NOW() - INTERVAL ? MINUTE"
        );
        $stmt->execute([$username, $this->lockout_minutes]);
        $result = $stmt->fetch();
        
        return $result['failed_count'] >= $this->max_attempts;
    }
}
?>