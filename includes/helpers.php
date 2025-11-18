<?php
/**
 * Helper Functions
 *
 * Common utility functions used throughout the application
 */

/**
 * Log error message to file
 *
 * @param string $message Error message
 * @param string $level Error level (ERROR, WARNING, INFO)
 */
function logError($message, $level = 'ERROR') {
    if (!defined('ENABLE_ERROR_LOG') || !ENABLE_ERROR_LOG) {
        return;
    }

    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

    // Create logs directory if it doesn't exist
    $logDir = dirname(ERROR_LOG_FILE);
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }

    // Write to log file
    error_log($logMessage, 3, ERROR_LOG_FILE);
}

/**
 * Generate random alphanumeric code
 *
 * @param int $length Length of the code
 * @return string Random code
 */
function generateCode($length = 6) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Removed ambiguous chars (0, O, 1, I)
    $code = '';
    $max = strlen($characters) - 1;

    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, $max)];
    }

    return $code;
}

/**
 * Check if code is unique in database
 *
 * @param string $code Code to check
 * @return bool True if unique, false if exists
 */
function isCodeUnique($code) {
    $db = Database::getInstance();
    $result = $db->fetchOne(
        "SELECT id FROM qr_codes WHERE code = ? LIMIT 1",
        "s",
        [$code]
    );
    return ($result === null);
}

/**
 * Generate unique QR code
 *
 * @param int $length Length of code
 * @param int $maxAttempts Maximum attempts to generate unique code
 * @return string|false Unique code or false on failure
 */
function generateUniqueCode($length = 6, $maxAttempts = 10) {
    for ($i = 0; $i < $maxAttempts; $i++) {
        $code = generateCode($length);
        if (isCodeUnique($code)) {
            return $code;
        }
    }

    logError("Failed to generate unique code after {$maxAttempts} attempts");
    return false;
}

/**
 * Validate URL format
 *
 * @param string $url URL to validate
 * @return bool True if valid, false otherwise
 */
function isValidUrl($url) {
    // Basic URL validation
    if (empty($url)) {
        return false;
    }

    // Check if URL has a valid format
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    // Check if URL has a valid scheme (http or https)
    $parsed = parse_url($url);
    if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
        return false;
    }

    return true;
}

/**
 * Sanitize input string
 *
 * @param string $input Input to sanitize
 * @return string Sanitized string
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize URL
 *
 * @param string $url URL to sanitize
 * @return string Sanitized URL
 */
function sanitizeUrl($url) {
    return filter_var(trim($url), FILTER_SANITIZE_URL);
}

/**
 * Send JSON response
 *
 * @param bool $success Success status
 * @param mixed $data Data to return
 * @param string $message Message
 * @param int $statusCode HTTP status code
 */
function jsonResponse($success, $data = null, $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');

    $response = [
        'success' => $success,
        'message' => $message
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Redirect to URL
 *
 * @param string $url URL to redirect to
 * @param int $statusCode HTTP status code (301 or 302)
 */
function redirect($url, $statusCode = 302) {
    http_response_code($statusCode);
    header("Location: " . $url);
    exit;
}

/**
 * Get current timestamp for database
 *
 * @return string MySQL formatted datetime
 */
function getCurrentTimestamp() {
    return date('Y-m-d H:i:s');
}

/**
 * Format date for display
 *
 * @param string $datetime MySQL datetime string
 * @param string $format Output format
 * @return string Formatted date
 */
function formatDate($datetime, $format = 'M d, Y g:i A') {
    return date($format, strtotime($datetime));
}

/**
 * Get base URL for QR codes
 *
 * @param string $code QR code
 * @return string Full URL for QR code
 */
function getQrUrl($code) {
    return BASE_URL . '/' . $code;
}

/**
 * Ensure directory exists with proper permissions
 *
 * @param string $dir Directory path
 * @return bool True on success, false on failure
 */
function ensureDirectory($dir) {
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0755, true)) {
            logError("Failed to create directory: {$dir}");
            return false;
        }
    }

    if (!is_writable($dir)) {
        logError("Directory not writable: {$dir}");
        return false;
    }

    return true;
}

/**
 * Delete file safely
 *
 * @param string $filePath Path to file
 * @return bool True on success, false on failure
 */
function deleteFile($filePath) {
    if (file_exists($filePath)) {
        if (!unlink($filePath)) {
            logError("Failed to delete file: {$filePath}");
            return false;
        }
    }
    return true;
}
?>
