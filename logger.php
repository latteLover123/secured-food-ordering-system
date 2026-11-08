
<?php
// logger.php - Security Audit Logging System
declare(strict_types=1);

/**
 * Records security events in a protected server-side log.
 * Never record passwords, OTPs, session IDs or tokens.
 */

function log_security_event(
    string $event,
    array $details = []
): void {

    // Only allow safe information in the audit log.
    $allowedKeys = [
        'username',
        'user_id',
        'target_user_id',
        'action',
        'reason'
    ];

    $safeDetails = [];

    foreach ($allowedKeys as $key) {
        if (isset($details[$key]) &&
            is_scalar($details[$key])) {

            $safeDetails[$key] = substr(
                (string)$details[$key],
                0,
                150
            );
        }
    }

    $record = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => substr($event, 0, 100),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
        'user_id' => $_SESSION['user_id'] ?? null,
        'details' => $safeDetails
    ];

    $logDirectory = __DIR__ . '/private_logs';
    $logFile = $logDirectory . '/security.log';

    // Create the log directory if it does not exist.
    if (!is_dir($logDirectory)) {
        if (!mkdir($logDirectory, 0700, true) &&
            !is_dir($logDirectory)) {
            error_log('Security log directory could not be created.');
            return;
        }
    }

    // Create the log file and append the event.
    $json = json_encode(
        $record,
        JSON_INVALID_UTF8_SUBSTITUTE
    );

    if ($json === false ||
        file_put_contents(
            $logFile,
            $json . PHP_EOL,
            FILE_APPEND | LOCK_EX
        ) === false) {

        error_log('Security audit logging failed.');
    }
}
