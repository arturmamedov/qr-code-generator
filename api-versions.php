<?php
/**
 * API Endpoint for QR Code Versions
 *
 * Handles CRUD operations for QR code versions
 * Protected by HTTP Basic Auth via .htaccess
 *
 * Actions:
 * - create_version: Create new version for existing QR code
 * - get_versions: Get all versions for a QR code
 * - get_version: Get single version details
 * - set_favorite: Set a version as favorite
 * - delete_version: Delete a version
 * - update_version: Update version name or style
 * - clone_version: Clone existing version
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
    case 'create_version':
        handleCreateVersion($input);
        break;

    case 'get_versions':
        handleGetVersions($input);
        break;

    case 'get_version':
        handleGetVersion($input);
        break;

    case 'set_favorite':
        handleSetFavorite($input);
        break;

    case 'delete_version':
        handleDeleteVersion($input);
        break;

    case 'update_version':
        handleUpdateVersion($input);
        break;

    case 'clone_version':
        handleCloneVersion($input);
        break;

    default:
        jsonResponse(false, null, 'Invalid action', 400);
}

/**
 * Handle create version request
 */
function handleCreateVersion($input) {
    global $db;

    // Validate required fields
    if (empty($input['qr_code_id'])) {
        jsonResponse(false, null, 'QR code ID is required', 400);
    }

    $qrCodeId = (int)$input['qr_code_id'];

    // Check if QR code exists
    $qrCode = $db->fetchOne("SELECT id, code FROM qr_codes WHERE id = ?", "i", [$qrCodeId]);
    if (!$qrCode) {
        jsonResponse(false, null, 'QR code not found', 404);
    }

    // Check version limit
    $limitCheck = checkVersionLimit($qrCodeId);
    if (!$limitCheck['allowed']) {
        jsonResponse(false, null, "Maximum version limit reached ({$limitCheck['limit']} versions)", 400);
    }

    // Get version name (default if not provided)
    $versionCount = getVersionCount($qrCodeId);
    $defaultName = 'Version ' . ($versionCount + 1);
    $versionName = !empty($input['version_name']) ? sanitizeInput($input['version_name']) : $defaultName;

    // Get style config (use provided or default)
    if (!empty($input['style_config'])) {
        $styleConfig = $input['style_config'];
    } else if (!empty($input['clone_from_version_id'])) {
        // Clone from existing version
        $cloneVersion = getVersion((int)$input['clone_from_version_id']);
        if ($cloneVersion) {
            $styleConfig = $cloneVersion['style_config'];
        } else {
            $styleConfig = getDefaultStyleConfig();
        }
    } else {
        $styleConfig = getDefaultStyleConfig();
    }

    // Ensure folder structure exists
    if (!ensureQrFolderStructure($qrCodeId)) {
        jsonResponse(false, null, 'Failed to create folder structure', 500);
    }

    // Insert version into database (temporarily without version ID for filename)
    $styleConfigJson = json_encode($styleConfig);
    $sql = "INSERT INTO qr_code_versions
            (qr_code_id, version_name, style_config, image_filename, is_favorite, created_at)
            VALUES (?, ?, ?, ?, 0, NOW())";

    $versionId = $db->insert($sql, "isss", [
        $qrCodeId,
        $versionName,
        $styleConfigJson,
        'v0.png' // Temporary, will update below
    ]);

    if (!$versionId) {
        jsonResponse(false, null, 'Failed to create version', 500);
    }

    // Update with correct filename using the version ID
    $imageFilename = getVersionFilename($versionId);
    $db->execute(
        "UPDATE qr_code_versions SET image_filename = ? WHERE id = ?",
        "si",
        [$imageFilename, $versionId]
    );

    // If this is the first version, set as favorite
    if ($versionCount == 0) {
        setFavoriteVersion($versionId);
    }

    logError("Version created: ID={$versionId}, QR Code ID={$qrCodeId}, Name={$versionName}", 'INFO');

    jsonResponse(true, [
        'version_id' => $versionId,
        'qr_code_id' => $qrCodeId,
        'version_name' => $versionName,
        'image_filename' => $imageFilename,
        'image_path' => getVersionImagePath($qrCodeId, $versionId),
        'style_config' => $styleConfig
    ], 'Version created successfully', 201);
}

/**
 * Handle get versions request
 */
function handleGetVersions($input) {
    // Validate required fields
    if (empty($input['qr_code_id'])) {
        jsonResponse(false, null, 'QR code ID is required', 400);
    }

    $qrCodeId = (int)$input['qr_code_id'];
    $page = isset($input['page']) ? max(1, (int)$input['page']) : 1;
    $limit = isset($input['limit']) ? min(50, max(1, (int)$input['limit'])) : null;

    $offset = $limit ? ($page - 1) * $limit : 0;

    // Get versions
    $versions = getQrVersions($qrCodeId, $limit, $offset);
    $totalCount = getVersionCount($qrCodeId);

    $response = [
        'versions' => $versions,
        'total_count' => $totalCount,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => $limit ? ceil($totalCount / $limit) : 1
    ];

    jsonResponse(true, $response, 'Versions retrieved successfully');
}

/**
 * Handle get single version request
 */
function handleGetVersion($input) {
    // Validate required fields
    if (empty($input['version_id'])) {
        jsonResponse(false, null, 'Version ID is required', 400);
    }

    $versionId = (int)$input['version_id'];

    // Get version
    $version = getVersion($versionId);

    if (!$version) {
        jsonResponse(false, null, 'Version not found', 404);
    }

    jsonResponse(true, $version, 'Version retrieved successfully');
}

/**
 * Handle set favorite request
 */
function handleSetFavorite($input) {
    // Validate required fields
    if (empty($input['version_id'])) {
        jsonResponse(false, null, 'Version ID is required', 400);
    }

    $versionId = (int)$input['version_id'];

    // Check if version exists
    $version = getVersion($versionId);
    if (!$version) {
        jsonResponse(false, null, 'Version not found', 404);
    }

    // Set as favorite
    if (setFavoriteVersion($versionId)) {
        logError("Version set as favorite: ID={$versionId}", 'INFO');
        jsonResponse(true, ['version_id' => $versionId], 'Version set as favorite successfully');
    } else {
        jsonResponse(false, null, 'Failed to set favorite version', 500);
    }
}

/**
 * Handle delete version request
 */
function handleDeleteVersion($input) {
    global $db;

    // Validate required fields
    if (empty($input['version_id'])) {
        jsonResponse(false, null, 'Version ID is required', 400);
    }

    $versionId = (int)$input['version_id'];

    // Get version details
    $version = getVersion($versionId);
    if (!$version) {
        jsonResponse(false, null, 'Version not found', 404);
    }

    $qrCodeId = $version['qr_code_id'];

    // Check if this is the last version (must have at least one)
    $versionCount = getVersionCount($qrCodeId);
    if ($versionCount <= 1) {
        jsonResponse(false, null, 'Cannot delete the last version. A QR code must have at least one version.', 400);
    }

    // If deleting favorite version, prompt to select new favorite
    if ($version['is_favorite']) {
        if (empty($input['new_favorite_id'])) {
            // Get other versions to suggest
            $otherVersions = $db->fetchAll(
                "SELECT id, version_name FROM qr_code_versions WHERE qr_code_id = ? AND id != ? ORDER BY created_at DESC LIMIT 5",
                "ii",
                [$qrCodeId, $versionId]
            );

            jsonResponse(false, [
                'requires_new_favorite' => true,
                'other_versions' => $otherVersions
            ], 'This is the favorite version. Please select a new favorite version before deleting.', 400);
        }

        // Set new favorite before deleting
        $newFavoriteId = (int)$input['new_favorite_id'];
        if (!setFavoriteVersion($newFavoriteId)) {
            jsonResponse(false, null, 'Failed to set new favorite version', 500);
        }
    }

    // Delete version from database
    $affected = $db->execute("DELETE FROM qr_code_versions WHERE id = ?", "i", [$versionId]);

    if ($affected > 0) {
        // Delete physical files
        deleteVersionFiles($qrCodeId, $versionId);

        logError("Version deleted: ID={$versionId}, QR Code ID={$qrCodeId}", 'INFO');
        jsonResponse(true, null, 'Version deleted successfully');
    } else {
        jsonResponse(false, null, 'Failed to delete version', 500);
    }
}

/**
 * Handle update version request
 */
function handleUpdateVersion($input) {
    global $db;

    // Validate required fields
    if (empty($input['version_id'])) {
        jsonResponse(false, null, 'Version ID is required', 400);
    }

    $versionId = (int)$input['version_id'];

    // Check if version exists
    $version = getVersion($versionId);
    if (!$version) {
        jsonResponse(false, null, 'Version not found', 404);
    }

    // Build update query dynamically based on provided fields
    $updates = [];
    $types = "";
    $params = [];

    if (!empty($input['version_name'])) {
        $updates[] = "version_name = ?";
        $types .= "s";
        $params[] = sanitizeInput($input['version_name']);
    }

    if (!empty($input['style_config'])) {
        $updates[] = "style_config = ?";
        $types .= "s";
        $params[] = json_encode($input['style_config']);
    }

    if (empty($updates)) {
        jsonResponse(false, null, 'No fields to update', 400);
    }

    // Add updated_at
    $updates[] = "updated_at = NOW()";

    // Add version ID to params
    $types .= "i";
    $params[] = $versionId;

    $sql = "UPDATE qr_code_versions SET " . implode(", ", $updates) . " WHERE id = ?";

    $affected = $db->execute($sql, $types, $params);

    if ($affected !== false) {
        logError("Version updated: ID={$versionId}", 'INFO');
        jsonResponse(true, ['version_id' => $versionId], 'Version updated successfully');
    } else {
        jsonResponse(false, null, 'Failed to update version', 500);
    }
}

/**
 * Handle clone version request
 */
function handleCloneVersion($input) {
    // Validate required fields
    if (empty($input['version_id'])) {
        jsonResponse(false, null, 'Version ID is required', 400);
    }

    $sourceVersionId = (int)$input['version_id'];

    // Get source version
    $sourceVersion = getVersion($sourceVersionId);
    if (!$sourceVersion) {
        jsonResponse(false, null, 'Source version not found', 404);
    }

    $qrCodeId = $sourceVersion['qr_code_id'];

    // Check version limit
    $limitCheck = checkVersionLimit($qrCodeId);
    if (!$limitCheck['allowed']) {
        jsonResponse(false, null, "Maximum version limit reached ({$limitCheck['limit']} versions)", 400);
    }

    // Create new version name
    $newName = !empty($input['new_name'])
        ? sanitizeInput($input['new_name'])
        : 'Copy of ' . $sourceVersion['version_name'];

    // Create new version by calling handleCreateVersion
    $createInput = [
        'action' => 'create_version',
        'qr_code_id' => $qrCodeId,
        'version_name' => $newName,
        'style_config' => $sourceVersion['style_config']
    ];

    handleCreateVersion($createInput);
}
?>
