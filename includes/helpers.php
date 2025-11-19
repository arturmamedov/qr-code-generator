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
 * @param string|null $customSlug Optional custom slug to use instead of generating
 * @return string|false Unique code or false on failure
 */
function generateUniqueCode($length = 6, $maxAttempts = 10, $customSlug = null) {
    // If custom slug is provided, validate and return it
    if ($customSlug !== null) {
        $validation = isValidSlug($customSlug);
        if ($validation['valid'] && isCodeUnique($customSlug)) {
            return $customSlug;
        }
        return false; // Invalid or not unique
    }

    // Otherwise generate random code
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

/**
 * Validate custom slug format
 *
 * @param string $slug Slug to validate
 * @return array ['valid' => bool, 'error' => string]
 */
function isValidSlug($slug) {
    // Check if empty
    if (empty($slug)) {
        return ['valid' => false, 'error' => 'Slug cannot be empty'];
    }

    // Check length
    $length = strlen($slug);
    if ($length < 1 || $length > QR_MAX_SLUG_LENGTH) {
        return ['valid' => false, 'error' => 'Slug must be between 1 and ' . QR_MAX_SLUG_LENGTH . ' characters'];
    }

    // Check for URL-safe characters only (letters, numbers, hyphens, underscores)
    // Allow mixed case as per requirements
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
        return ['valid' => false, 'error' => 'Slug can only contain letters, numbers, hyphens, and underscores'];
    }

    // Check if slug is reserved
    if (in_array(strtolower($slug), array_map('strtolower', RESERVED_SLUGS))) {
        return ['valid' => false, 'error' => 'This slug is reserved and cannot be used'];
    }

    return ['valid' => true, 'error' => ''];
}

/**
 * Generate slug suggestions when the desired slug is taken
 *
 * @param string $baseSlug Base slug to generate suggestions from
 * @param int $count Number of suggestions to generate
 * @return array Array of suggested slugs
 */
function generateSlugSuggestions($baseSlug, $count = 5) {
    $suggestions = [];
    $db = Database::getInstance();

    // Strategy 1: Append numbers (slug-2, slug-3, etc.)
    for ($i = 2; $i <= $count + 1; $i++) {
        $suggestion = $baseSlug . '-' . $i;

        // Check length limit
        if (strlen($suggestion) > QR_MAX_SLUG_LENGTH) {
            continue;
        }

        // Check if unique
        if (isCodeUnique($suggestion)) {
            $suggestions[] = $suggestion;
            if (count($suggestions) >= $count) {
                return $suggestions;
            }
        }
    }

    // Strategy 2: Append current year (slug-2025)
    $yearSuggestion = $baseSlug . '-' . date('Y');
    if (strlen($yearSuggestion) <= QR_MAX_SLUG_LENGTH && isCodeUnique($yearSuggestion)) {
        $suggestions[] = $yearSuggestion;
        if (count($suggestions) >= $count) {
            return $suggestions;
        }
    }

    // Strategy 3: Append current month-year (slug-nov-2025)
    $monthYearSuggestion = $baseSlug . '-' . strtolower(date('M-Y'));
    if (strlen($monthYearSuggestion) <= QR_MAX_SLUG_LENGTH && isCodeUnique($monthYearSuggestion)) {
        $suggestions[] = $monthYearSuggestion;
        if (count($suggestions) >= $count) {
            return $suggestions;
        }
    }

    // Strategy 4: Append short random code
    for ($i = 0; $i < 10 && count($suggestions) < $count; $i++) {
        $randomCode = strtolower(generateCode(3)); // 3-char random code
        $randomSuggestion = $baseSlug . '-' . $randomCode;

        if (strlen($randomSuggestion) <= QR_MAX_SLUG_LENGTH && isCodeUnique($randomSuggestion)) {
            $suggestions[] = $randomSuggestion;
        }
    }

    return array_slice($suggestions, 0, $count);
}
?>
