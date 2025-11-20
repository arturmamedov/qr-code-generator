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

// Check if image is present
if (!isset($_FILES['image'])) {
    http_response_code(400);
    exit('Missing image file');
}

$image = $_FILES['image'];

// Check for new versioned format (qr_code_id + version_id) or old format (code)
if (isset($_POST['qr_code_id']) && isset($_POST['version_id'])) {
    // New versioned format
    $qrCodeId = (int)$_POST['qr_code_id'];
    $versionId = (int)$_POST['version_id'];

    if ($qrCodeId <= 0 || $versionId <= 0) {
        http_response_code(400);
        exit('Invalid QR code ID or version ID');
    }

    $useVersioning = true;
} elseif (isset($_POST['code'])) {
    // Old format for backward compatibility
    $code = sanitizeInput($_POST['code']);

    // Validate code format
    if (!preg_match('/^[A-Za-z0-9_-]{1,33}$/', $code)) {
        http_response_code(400);
        exit('Invalid code format');
    }

    $useVersioning = false;
} else {
    http_response_code(400);
    exit('Missing required parameters (qr_code_id+version_id or code)');
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

// Determine file path based on versioning
if ($useVersioning) {
    // New versioned format: generated/qr-code-{id}/v{version_id}.png
    if (!ensureQrFolderStructure($qrCodeId)) {
        http_response_code(500);
        exit('Failed to create folder structure');
    }

    $filename = getVersionFilename($versionId);
    $filepath = getVersionImagePath($qrCodeId, $versionId);
} else {
    // Old format: generated/{code}.png (backward compatibility)
    if (!ensureDirectory(GENERATED_PATH)) {
        http_response_code(500);
        exit('Failed to create generated directory');
    }

    $filename = $code . '.png';
    $filepath = GENERATED_PATH . '/' . $filename;
}

// Save image (always as PNG for consistency)
if (move_uploaded_file($image['tmp_name'], $filepath)) {
    logError("QR image saved: {$filename}", 'INFO');
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'filepath' => $filepath
    ]);
} else {
    logError("Failed to save QR image: {$filename}");
    http_response_code(500);
    exit('Failed to save image');
}
?>
