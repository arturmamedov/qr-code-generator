<?php
/**
 * QR Code Manager - Configuration File
 *
 * SETUP INSTRUCTIONS:
 * 1. Copy this file to 'config.php'
 * 2. Update the values below with your actual database credentials
 * 3. Set the correct BASE_URL for your domain
 * 4. DO NOT commit config.php to version control!
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// Application Configuration
define('BASE_URL', 'https://qr.nestshostels.com');

// Directory Paths
define('ROOT_PATH', __DIR__);
define('GENERATED_PATH', ROOT_PATH . '/generated');
define('LOGS_PATH', ROOT_PATH . '/logs');

// Error Logging
define('ENABLE_ERROR_LOG', true);
define('ERROR_LOG_FILE', LOGS_PATH . '/error.log');

// QR Code Defaults
define('QR_CODE_LENGTH', 6); // Length of auto-generated short codes
define('QR_MAX_SLUG_LENGTH', 33); // Maximum length for custom slugs
define('QR_DEFAULT_SIZE', 300); // Default QR code size in pixels

// Reserved Slugs (cannot be used as QR codes)
// Add additional reserved words to this array as needed
define('RESERVED_SLUGS', [
    'admin', 'api', 'create', 'edit', 'index',
    'generated', 'assets', 'includes', 'logs',
    'config', 'database', 'diagnostic', 'save-image',
    'r', 'qr', 'delete', 'update', 'get'
]);

// Timezone
date_default_timezone_set('UTC');
?>
