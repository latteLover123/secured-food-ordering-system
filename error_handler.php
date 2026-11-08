
<?php
// error_handler.php - Global Exception and Error Handler
declare(strict_types=1);

require_once __DIR__ . '/logger.php';

// Do not display technical errors to website visitors.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

error_reporting(E_ALL);

/**
 * Handles PHP warnings, notices and errors.
 */
set_error_handler(function (
    int $severity,
    string $message,
    string $file,
    int $line
): bool {

    // Respect errors suppressed with @.
    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new ErrorException(
        $message,
        0,
        $severity,
        $file,
        $line
    );
});

/**
 * Handles uncaught exceptions.
 */
set_exception_handler(function (Throwable $exception): void {

    // Record technical information in the server log.
    log_security_event('UNCAUGHT_EXCEPTION', [
        'action' => get_class($exception),
        'reason' => 'File: ' . basename($exception->getFile())
            . ' Line: ' . $exception->getLine()
    ]);

    show_generic_error();
});

/**
 * Handles fatal errors that do not reach the exception handler.
 */
register_shutdown_function(function (): void {

    $error = error_get_last();

    if ($error !== null &&
        in_array($error['type'], [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR
        ], true)) {

        log_security_event('FATAL_PHP_ERROR', [
            'action' => 'PHP fatal error',
            'reason' => 'File: ' . basename($error['file'])
                . ' Line: ' . $error['line']
        ]);

        show_generic_error();
    }
});

/**
 * Redirects visitors to a generic error page.
 */
function show_generic_error(): void {

    if (!headers_sent()) {
        http_response_code(500);

        header('Location: error_page.html');
    } else {
        // Fallback if headers have already been sent.
        http_response_code(500);

        echo '<h2>Something went wrong.</h2>';
        echo '<p>Please try again later.</p>';
    }
    
    exit();
}
