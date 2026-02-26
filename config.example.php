<?php
/**
 * QR Code Manager - Configuration File
 *
 * This file reads configuration from environment variables with fallback defaults.
 *
 * SETUP OPTIONS:
 * Option A (Shared Hosting): Copy to config.php and edit values directly
 * Option B (Local Dev): Create a .env file from .env.example
 * Option C (Coolify/Docker): Set environment variables in your platform
 *
 * DO NOT commit config.php or .env to version control!
 */

// Database Driver: 'mysql' or 'sqlite'
define('DB_DRIVER', env('DB_DRIVER', 'mysql'));

// MySQL Configuration (used when DB_DRIVER is 'mysql')
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', ''));
define('DB_USER', env('DB_USER', ''));
define('DB_PASS', env('DB_PASS', ''));

// SQLite Configuration (used when DB_DRIVER is 'sqlite')
define('DB_SQLITE_PATH', env('DB_SQLITE_PATH', __DIR__ . '/data/qr_codes.db'));

// Application Configuration
define('BASE_URL', env('BASE_URL', ''));

// Directory Paths
define('ROOT_PATH', __DIR__);
define('GENERATED_PATH', env('GENERATED_PATH', ROOT_PATH . '/generated'));
define('LOGS_PATH', env('LOGS_PATH', ROOT_PATH . '/logs'));

// Error Logging
define('ENABLE_ERROR_LOG', env('ENABLE_ERROR_LOG', true));
define('ERROR_LOG_FILE', env('ERROR_LOG_FILE', LOGS_PATH . '/error.log'));

// QR Code Defaults
define('QR_CODE_LENGTH', (int) env('QR_CODE_LENGTH', 6));
define('QR_MAX_SLUG_LENGTH', (int) env('QR_MAX_SLUG_LENGTH', 33));
define('QR_DEFAULT_SIZE', (int) env('QR_DEFAULT_SIZE', 300));

// Reserved Slugs (cannot be used as QR codes)
// Add additional reserved words to this array as needed
define('RESERVED_SLUGS', [
    'admin', 'api', 'create', 'edit', 'index',
    'generated', 'assets', 'includes', 'logs',
    'config', 'database', 'diagnostic', 'save-image',
    'r', 'qr', 'delete', 'update', 'get'
]);

// Timezone
date_default_timezone_set(env('TIMEZONE', 'UTC'));
?>
