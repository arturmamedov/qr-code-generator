<?php
/**
 * API Endpoint
 *
 * Handles all CRUD operations for QR codes via JSON API
 * Protected by HTTP Basic Auth via .htaccess
 *
 * Actions:
 * - create: Create new QR code
 * - update: Update existing QR code
 * - delete: Delete QR code
 * - get_all: Get all QR codes
 * - get_single: Get single QR code
 * - reset_clicks: Reset click counter
 */

require_once __DIR__ . '/includes/init.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Only POST requests are allowed', 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Get action from input
$action = $input['action'] ?? '';

// Route to appropriate handler
switch ($action) {
    case 'create':
        handleCreate($input);
        break;

    case 'update':
        handleUpdate($input);
        break;

    case 'delete':
        handleDelete($input);
        break;

    case 'get_all':
        handleGetAll();
        break;

    case 'get_single':
        handleGetSingle($input);
        break;

    case 'reset_clicks':
        handleResetClicks($input);
        break;

    default:
        jsonResponse(false, null, 'Invalid action', 400);
}

/**
 * Handle create QR code request
 */
function handleCreate($input) {
    global $db;

    // Validate required fields
    if (empty($input['title'])) {
        jsonResponse(false, null, 'Title is required', 400);
    }

    if (empty($input['destination_url'])) {
        jsonResponse(false, null, 'Destination URL is required', 400);
    }

    // Validate URL format
    $destinationUrl = sanitizeUrl($input['destination_url']);
    if (!isValidUrl($destinationUrl)) {
        jsonResponse(false, null, 'Invalid destination URL format', 400);
    }

    // Sanitize inputs
    $title = sanitizeInput($input['title']);
    $description = sanitizeInput($input['description'] ?? '');
    $tags = sanitizeInput($input['tags'] ?? '');

    // Generate unique code
    $code = generateUniqueCode(QR_CODE_LENGTH);
    if (!$code) {
        jsonResponse(false, null, 'Failed to generate unique code. Please try again.', 500);
    }

    // Insert into database
    $sql = "INSERT INTO qr_codes (code, title, description, destination_url, tags)
            VALUES (?, ?, ?, ?, ?)";

    $insertId = $db->insert($sql, "sssss", [
        $code,
        $title,
        $description,
        $destinationUrl,
        $tags
    ]);

    if ($insertId) {
        logError("QR code created: ID={$insertId}, Code={$code}", 'INFO');

        jsonResponse(true, [
            'id' => $insertId,
            'code' => $code,
            'qr_url' => getQrUrl($code)
        ], 'QR code created successfully', 201);
    } else {
        jsonResponse(false, null, 'Failed to create QR code', 500);
    }
}

/**
 * Handle update QR code request
 */
function handleUpdate($input) {
    global $db;

    // Validate ID
    if (empty($input['id'])) {
        jsonResponse(false, null, 'QR code ID is required', 400);
    }

    $id = (int)$input['id'];

    // Check if QR code exists
    $existing = $db->fetchOne("SELECT id FROM qr_codes WHERE id = ?", "i", [$id]);
    if (!$existing) {
        jsonResponse(false, null, 'QR code not found', 404);
    }

    // Validate required fields
    if (empty($input['title'])) {
        jsonResponse(false, null, 'Title is required', 400);
    }

    if (empty($input['destination_url'])) {
        jsonResponse(false, null, 'Destination URL is required', 400);
    }

    // Validate URL format
    $destinationUrl = sanitizeUrl($input['destination_url']);
    if (!isValidUrl($destinationUrl)) {
        jsonResponse(false, null, 'Invalid destination URL format', 400);
    }

    // Sanitize inputs
    $title = sanitizeInput($input['title']);
    $description = sanitizeInput($input['description'] ?? '');
    $tags = sanitizeInput($input['tags'] ?? '');

    // Update database
    $sql = "UPDATE qr_codes
            SET title = ?, description = ?, destination_url = ?, tags = ?, updated_at = NOW()
            WHERE id = ?";

    $affected = $db->execute($sql, "ssssi", [
        $title,
        $description,
        $destinationUrl,
        $tags,
        $id
    ]);

    if ($affected !== false) {
        logError("QR code updated: ID={$id}", 'INFO');
        jsonResponse(true, ['id' => $id], 'QR code updated successfully');
    } else {
        jsonResponse(false, null, 'Failed to update QR code', 500);
    }
}

/**
 * Handle delete QR code request
 */
function handleDelete($input) {
    global $db;

    // Validate ID
    if (empty($input['id'])) {
        jsonResponse(false, null, 'QR code ID is required', 400);
    }

    $id = (int)$input['id'];

    // Get QR code details before deleting
    $qrCode = $db->fetchOne("SELECT code FROM qr_codes WHERE id = ?", "i", [$id]);
    if (!$qrCode) {
        jsonResponse(false, null, 'QR code not found', 404);
    }

    // Delete from database
    $affected = $db->execute("DELETE FROM qr_codes WHERE id = ?", "i", [$id]);

    if ($affected > 0) {
        // Delete generated image file
        $imagePath = GENERATED_PATH . '/' . $qrCode['code'] . '.png';
        deleteFile($imagePath);

        logError("QR code deleted: ID={$id}, Code={$qrCode['code']}", 'INFO');
        jsonResponse(true, null, 'QR code deleted successfully');
    } else {
        jsonResponse(false, null, 'Failed to delete QR code', 500);
    }
}

/**
 * Handle get all QR codes request
 */
function handleGetAll() {
    global $db;

    // Fetch all QR codes ordered by most recent first
    $qrCodes = $db->fetchAll(
        "SELECT id, code, title, description, destination_url, click_count, tags,
                created_at, updated_at
         FROM qr_codes
         ORDER BY created_at DESC"
    );

    // Add QR URL to each record
    foreach ($qrCodes as &$qr) {
        $qr['qr_url'] = getQrUrl($qr['code']);
        $qr['image_url'] = BASE_URL . '/generated/' . $qr['code'] . '.png';
    }

    jsonResponse(true, $qrCodes, 'QR codes retrieved successfully');
}

/**
 * Handle get single QR code request
 */
function handleGetSingle($input) {
    global $db;

    // Validate ID
    if (empty($input['id'])) {
        jsonResponse(false, null, 'QR code ID is required', 400);
    }

    $id = (int)$input['id'];

    // Fetch QR code
    $qrCode = $db->fetchOne(
        "SELECT id, code, title, description, destination_url, click_count, tags,
                created_at, updated_at
         FROM qr_codes
         WHERE id = ?",
        "i",
        [$id]
    );

    if ($qrCode) {
        $qrCode['qr_url'] = getQrUrl($qrCode['code']);
        $qrCode['image_url'] = BASE_URL . '/generated/' . $qrCode['code'] . '.png';
        jsonResponse(true, $qrCode, 'QR code retrieved successfully');
    } else {
        jsonResponse(false, null, 'QR code not found', 404);
    }
}

/**
 * Handle reset clicks request
 */
function handleResetClicks($input) {
    global $db;

    // Validate ID
    if (empty($input['id'])) {
        jsonResponse(false, null, 'QR code ID is required', 400);
    }

    $id = (int)$input['id'];

    // Reset click count
    $affected = $db->execute(
        "UPDATE qr_codes SET click_count = 0 WHERE id = ?",
        "i",
        [$id]
    );

    if ($affected !== false) {
        logError("Click count reset: ID={$id}", 'INFO');
        jsonResponse(true, null, 'Click count reset successfully');
    } else {
        jsonResponse(false, null, 'Failed to reset click count', 500);
    }
}
?>
