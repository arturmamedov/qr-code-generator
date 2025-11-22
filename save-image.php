<?php
/**
 * Save QR Code Image
 *
 * Handles uploading and saving QR code images to the generated directory
 * Protected by HTTP Basic Auth via .htaccess
 */

require_once __DIR__ . '/includes/init.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Check if image and code are present
if (!isset($_FILES['image']) || !isset($_POST['code'])) {
    http_response_code(400);
    exit('Missing required parameters');
}

$code = sanitizeInput($_POST['code']);
$image = $_FILES['image'];

// Validate code format (supports custom slugs with hyphens and underscores, 1-33 chars)
if (!preg_match('/^[A-Za-z0-9_-]{1,33}$/', $code)) {
    http_response_code(400);
    exit('Invalid code format');
}

// Validate image upload
if ($image['error'] !== UPLOAD_ERR_OK) {
    logError("Image upload error: " . $image['error']);
    http_response_code(500);
    exit('Image upload failed');
}

// Validate file type
$allowedTypes = ['image/png', 'image/jpeg'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $image['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    exit('Invalid image type');
}

// Ensure generated directory exists
if (!ensureDirectory(GENERATED_PATH)) {
    http_response_code(500);
    exit('Failed to create generated directory');
}

// Save image
$extension = ($mimeType === 'image/png') ? 'png' : 'jpg';
$filename = $code . '.png'; // Always save as PNG for consistency
$filepath = GENERATED_PATH . '/' . $filename;

if (move_uploaded_file($image['tmp_name'], $filepath)) {
    logError("QR image saved: {$filename}", 'INFO');
    http_response_code(200);
    echo json_encode(['success' => true, 'filename' => $filename]);
} else {
    logError("Failed to save QR image: {$filename}");
    http_response_code(500);
    exit('Failed to save image');
}
?>
