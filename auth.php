<?php
// auth.php - Core Authentication and Cryptographic Library
declare(strict_types=1);

require_once 'session.php';

/**
 * Validates password complexity against institutional security baseline.
 * Enforces length bounds to prevent DoS via payload exhaustion (CWE-120/CWE-20).
 */
function validate_password_strength(string $password): bool {
    $length = mb_strlen($password, '8bit');
    
    // Bounds checking: minimum 12 chars, maximum 128 chars (prevent CPU exhaustion)
    if ($length < 12 || $length > 128) {
        return false;
    }

    // Require uppercase, lowercase, number, and special character
    $has_upper   = preg_match('/[A-Z]/',$password);
    $has_lower   = preg_match('/[a-z]/',$password);
    $has_digit   = preg_match('/[0-9]/',$password);
    $has_special = preg_match('/[^A-Za-z0-9]/',$password);

    return ($has_upper &&$has_lower && $has_digit &&$has_special);
}

/**
 * Generates an Argon2id salted password hash.
 * Memory-hard hashing neutralizes GPU/ASIC brute-force cracking.
 */
function hash_password_secure(string $password): string {$options = [
        'memory_cost' => 65536, // 64 MB memory allocation
        'time_cost'   => 4,     // 4 computation iterations
        'threads'     => 2      // 2 parallel processing threads
    ];

    $hash = password_hash($password, PASSWORD_ARGON2ID,$options);

    if ($hash === false) {
        throw new RuntimeException("Cryptographic hashing engine failed to produce a valid digest.");
    }

    return $hash;
}

/**
 * Registers a new user into the database using parameterized execution.
 */
function register_user(PDO $pdo, string$username, string $email, string$password): array {
    // 1. Syntactic Validation: Whitelist format checks
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/',$username)) {
        return ['success' => false, 'error' => 'Username must be 3-30 alphanumeric characters.'];
    }

    // RFC 5321 length limits (local part <= 63, total <= 254) and syntax
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254)
         {
        return ['success' => false, 'error' => 'Invalid email address syntax.'];
    }

    // 2. Semantic Validation: Password strength rules
    if (!validate_password_strength($password)) {
        return [
            'success' => false, 
            'error' => 'Password must be at least 12 characters and include uppercase, lowercase, a number, and a symbol.'
        ];
    }

    try {
        // 3. Collision Check: Verify username/email uniqueness via Prepared Statement
        $check_stmt =$pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $check_stmt->execute([$username,$email]);
        
        if ($check_stmt->fetch()) {
            return ['success' => false, 'error' => 'Username or email already registered.'];
        }

        // 4. Credential Hashing
        $hashed_password = hash_password_secure($password);

        // 5. Ingestion: Write user with default MFA enabled
        $insert_stmt =$pdo->prepare("
            INSERT INTO users (username, email, password_hash, mfa_enabled, role, created_at)
            VALUES (?, ?, ?, 1, 'user', NOW())
        ");
    $insert_stmt->execute([$username, $email, $hashed_password]);
            return ['success' => true, 'error' => null];
        } catch (PDOException $e) {
            // Suppress raw database error to prevent information disclosure (CWE-209)
            return ['success' => false, 'error' => 'Database error occurred. Please try again.'];
        }
}