<?php
/**
 * Application Initialization
 *
 * Include this file at the top of every PHP page to load
 * configuration, database, and helper functions.
 */

// Start session for admin authentication
session_start();

// Load environment helper (must be before config)
require_once __DIR__ . '/env-loader.php';

// Load .env file if it exists (local development)
loadEnvFile(__DIR__ . '/../.env');

// Load configuration (defines all constants)
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
} else {
    // No config.php (e.g., Coolify deployment) — use config.example.php
    require_once __DIR__ . '/../config.example.php';
}

// Load database class
require_once __DIR__ . '/Database.php';

// Load helper functions
require_once __DIR__ . '/helpers.php';

// Load version helper functions
require_once __DIR__ . '/version-helpers.php';

// Load auth middleware
require_once __DIR__ . '/auth.php';

// Set error reporting based on environment
if (defined('ENABLE_ERROR_LOG') && ENABLE_ERROR_LOG) {
    // Log errors but don't display them
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
} else {
    // Development mode - show errors
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Set custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $errorTypes = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_NOTICE => 'NOTICE',
        E_USER_ERROR => 'USER ERROR',
        E_USER_WARNING => 'USER WARNING',
        E_USER_NOTICE => 'USER NOTICE'
    ];

    $type = $errorTypes[$errno] ?? 'UNKNOWN';
    $message = "{$type}: {$errstr} in {$errfile} on line {$errline}";
    logError($message);

    // Return false to use default error handler for fatal errors
    return false;
});

// Set exception handler
set_exception_handler(function($exception) {
    $message = "EXCEPTION: " . $exception->getMessage() .
               " in " . $exception->getFile() .
               " on line " . $exception->getLine();
    logError($message);

    // Show user-friendly error
    if (defined('ENABLE_ERROR_LOG') && ENABLE_ERROR_LOG) {
        die("An error occurred. Please check the logs or contact support.");
    } else {
        die($message);
    }
});

// Validate required configuration before attempting database connection
if (defined('DB_DRIVER') && DB_DRIVER === 'sqlite') {
    validateConfig(['BASE_URL']);
} else {
    validateConfig(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'BASE_URL']);
}

// Ensure required directories exist
ensureDirectory(GENERATED_PATH);
ensureDirectory(LOGS_PATH);

// Initialize database connection
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    logError("Failed to initialize database: " . $e->getMessage());
    die("Database initialization failed. Please check configuration.");
}
?>
