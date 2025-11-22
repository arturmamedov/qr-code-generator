<?php
/**
 * Version Helper Functions
 *
 * Utility functions for QR code version management
 */

/**
 * Get QR code folder path
 *
 * @param int $qrCodeId QR code ID
 * @return string Absolute path to QR code folder
 */
function getQrFolder($qrCodeId) {
    return GENERATED_PATH . '/qr-code-' . $qrCodeId;
}

/**
 * Get logos folder path for a QR code
 *
 * @param int $qrCodeId QR code ID
 * @return string Absolute path to logos folder
 */
function getLogosFolder($qrCodeId) {
    return getQrFolder($qrCodeId) . '/logos';
}

/**
 * Get version image filename
 *
 * @param int $versionId Version ID
 * @return string Image filename (e.g., "v1.png")
 */
function getVersionFilename($versionId) {
    return 'v' . $versionId . '.png';
}

/**
 * Get version image path
 *
 * @param int $qrCodeId QR code ID
 * @param int $versionId Version ID
 * @return string Absolute path to version image
 */
function getVersionImagePath($qrCodeId, $versionId) {
    return getQrFolder($qrCodeId) . '/' . getVersionFilename($versionId);
}

/**
 * Get version logo filename
 *
 * @param int $versionId Version ID
 * @param string $extension File extension (default: png)
 * @return string Logo filename (e.g., "logo_v1.png")
 */
function getVersionLogoFilename($versionId, $extension = 'png') {
    return 'logo_v' . $versionId . '.' . $extension;
}

/**
 * Get version logo path
 *
 * @param int $qrCodeId QR code ID
 * @param int $versionId Version ID
 * @param string $extension File extension (default: png)
 * @return string Absolute path to version logo
 */
function getVersionLogoPath($qrCodeId, $versionId, $extension = 'png') {
    return getLogosFolder($qrCodeId) . '/' . getVersionLogoFilename($versionId, $extension);
}

/**
 * Ensure QR code folder structure exists
 *
 * @param int $qrCodeId QR code ID
 * @return bool True on success, false on failure
 */
function ensureQrFolderStructure($qrCodeId) {
    $qrFolder = getQrFolder($qrCodeId);
    $logosFolder = getLogosFolder($qrCodeId);

    // Create main QR folder
    if (!file_exists($qrFolder)) {
        if (!mkdir($qrFolder, 0755, true)) {
            logError("Failed to create QR folder: {$qrFolder}");
            return false;
        }
    }

    // Create logos subfolder
    if (!file_exists($logosFolder)) {
        if (!mkdir($logosFolder, 0755, true)) {
            logError("Failed to create logos folder: {$logosFolder}");
            return false;
        }
    }

    return true;
}

/**
 * Delete QR code folder and all contents
 *
 * @param int $qrCodeId QR code ID
 * @return bool True on success, false on failure
 */
function deleteQrFolder($qrCodeId) {
    $qrFolder = getQrFolder($qrCodeId);

    if (!file_exists($qrFolder)) {
        return true; // Already deleted
    }

    // Delete all files in logos folder
    $logosFolder = getLogosFolder($qrCodeId);
    if (file_exists($logosFolder)) {
        $logoFiles = glob($logosFolder . '/*');
        foreach ($logoFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($logosFolder);
    }

    // Delete all version images
    $versionFiles = glob($qrFolder . '/v*.png');
    foreach ($versionFiles as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }

    // Delete main folder
    if (!rmdir($qrFolder)) {
        logError("Failed to delete QR folder: {$qrFolder}");
        return false;
    }

    return true;
}

/**
 * Delete a specific version's files
 *
 * @param int $qrCodeId QR code ID
 * @param int $versionId Version ID
 * @return bool True on success, false on failure
 */
function deleteVersionFiles($qrCodeId, $versionId) {
    $success = true;

    // Delete version image
    $imagePath = getVersionImagePath($qrCodeId, $versionId);
    if (file_exists($imagePath)) {
        if (!unlink($imagePath)) {
            logError("Failed to delete version image: {$imagePath}");
            $success = false;
        }
    }

    // Delete version logo (try common extensions)
    $extensions = ['png', 'jpg', 'jpeg'];
    foreach ($extensions as $ext) {
        $logoPath = getVersionLogoPath($qrCodeId, $versionId, $ext);
        if (file_exists($logoPath)) {
            if (!unlink($logoPath)) {
                logError("Failed to delete version logo: {$logoPath}");
                $success = false;
            }
        }
    }

    return $success;
}

/**
 * Get all versions for a QR code
 *
 * @param int $qrCodeId QR code ID
 * @param int $limit Optional limit
 * @param int $offset Optional offset
 * @return array Array of version records
 */
function getQrVersions($qrCodeId, $limit = null, $offset = 0) {
    $db = Database::getInstance();

    $sql = "SELECT id, qr_code_id, version_name, style_config, image_filename,
                   is_favorite, created_at, updated_at
            FROM qr_code_versions
            WHERE qr_code_id = ?
            ORDER BY created_at DESC";

    if ($limit !== null) {
        $sql .= " LIMIT ? OFFSET ?";
        $versions = $db->fetchAll($sql, "iii", [$qrCodeId, $limit, $offset]);
    } else {
        $versions = $db->fetchAll($sql, "i", [$qrCodeId]);
    }

    // Add image URLs to each version
    foreach ($versions as &$version) {
        $version['image_url'] = BASE_URL . '/generated/qr-code-' . $qrCodeId . '/' . $version['image_filename'];
        $version['style_config'] = json_decode($version['style_config'], true);
    }

    return $versions;
}

/**
 * Get version count for a QR code
 *
 * @param int $qrCodeId QR code ID
 * @return int Number of versions
 */
function getVersionCount($qrCodeId) {
    $db = Database::getInstance();
    $result = $db->fetchOne(
        "SELECT COUNT(*) as count FROM qr_code_versions WHERE qr_code_id = ?",
        "i",
        [$qrCodeId]
    );
    return $result ? (int)$result['count'] : 0;
}

/**
 * Get a single version by ID
 *
 * @param int $versionId Version ID
 * @return array|null Version record or null if not found
 */
function getVersion($versionId) {
    $db = Database::getInstance();
    $version = $db->fetchOne(
        "SELECT id, qr_code_id, version_name, style_config, image_filename,
                is_favorite, created_at, updated_at
         FROM qr_code_versions
         WHERE id = ?",
        "i",
        [$versionId]
    );

    if ($version) {
        $version['image_url'] = BASE_URL . '/generated/qr-code-' . $version['qr_code_id'] . '/' . $version['image_filename'];
        $version['style_config'] = json_decode($version['style_config'], true);
    }

    return $version;
}

/**
 * Get favorite version for a QR code
 *
 * @param int $qrCodeId QR code ID
 * @return array|null Favorite version record or null if not found
 */
function getFavoriteVersion($qrCodeId) {
    $db = Database::getInstance();
    $version = $db->fetchOne(
        "SELECT id, qr_code_id, version_name, style_config, image_filename,
                is_favorite, created_at, updated_at
         FROM qr_code_versions
         WHERE qr_code_id = ? AND is_favorite = 1
         LIMIT 1",
        "i",
        [$qrCodeId]
    );

    if ($version) {
        $version['image_url'] = BASE_URL . '/generated/qr-code-' . $version['qr_code_id'] . '/' . $version['image_filename'];
        $version['style_config'] = json_decode($version['style_config'], true);
    }

    return $version;
}

/**
 * Set a version as favorite
 *
 * @param int $versionId Version ID
 * @return bool True on success, false on failure
 */
function setFavoriteVersion($versionId) {
    $db = Database::getInstance();

    // Get version details
    $version = $db->fetchOne(
        "SELECT id, qr_code_id FROM qr_code_versions WHERE id = ?",
        "i",
        [$versionId]
    );

    if (!$version) {
        return false;
    }

    $qrCodeId = $version['qr_code_id'];

    // Start transaction
    $db->beginTransaction();

    try {
        // Unset all favorites for this QR code
        $db->execute(
            "UPDATE qr_code_versions SET is_favorite = 0 WHERE qr_code_id = ?",
            "i",
            [$qrCodeId]
        );

        // Set this version as favorite
        $db->execute(
            "UPDATE qr_code_versions SET is_favorite = 1 WHERE id = ?",
            "i",
            [$versionId]
        );

        // Update qr_codes table
        $db->execute(
            "UPDATE qr_codes SET favorite_version_id = ? WHERE id = ?",
            "ii",
            [$versionId, $qrCodeId]
        );

        $db->commit();
        return true;

    } catch (Exception $e) {
        $db->rollback();
        logError("Failed to set favorite version: " . $e->getMessage());
        return false;
    }
}

/**
 * Create default style config
 *
 * @return array Default style configuration
 */
function getDefaultStyleConfig() {
    return [
        'width' => 300,
        'height' => 300,
        'margin' => 10,
        'dotsOptions' => [
            'type' => 'rounded',
            'color' => '#000000'
        ],
        'backgroundOptions' => [
            'color' => '#ffffff'
        ],
        'cornersSquareOptions' => [
            'type' => 'square',
            'color' => '#000000'
        ],
        'cornersDotOptions' => [
            'type' => 'square',
            'color' => '#000000'
        ],
        'imageOptions' => [
            'hideBackgroundDots' => true,
            'imageSize' => 0.4,
            'margin' => 0
        ],
        'logo' => [
            'hasLogo' => false,
            'logoPath' => null,
            'logoFilename' => null
        ]
    ];
}

/**
 * Validate version limit
 *
 * @param int $qrCodeId QR code ID
 * @param int $maxVersions Maximum allowed versions (default: 20)
 * @return array ['allowed' => bool, 'count' => int, 'limit' => int]
 */
function checkVersionLimit($qrCodeId, $maxVersions = 20) {
    $count = getVersionCount($qrCodeId);

    return [
        'allowed' => $count < $maxVersions,
        'count' => $count,
        'limit' => $maxVersions
    ];
}

/**
 * Handle logo upload for a version
 *
 * @param int $qrCodeId QR code ID
 * @param int $versionId Version ID
 * @param array $uploadedFile $_FILES array entry
 * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
 */
function handleVersionLogoUpload($qrCodeId, $versionId, $uploadedFile) {
    // Validate upload
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'path' => null,
            'error' => 'Logo upload failed'
        ];
    }

    // Validate file type
    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return [
            'success' => false,
            'path' => null,
            'error' => 'Invalid logo file type. Only PNG and JPG allowed.'
        ];
    }

    // Ensure folders exist
    if (!ensureQrFolderStructure($qrCodeId)) {
        return [
            'success' => false,
            'path' => null,
            'error' => 'Failed to create folder structure'
        ];
    }

    // Determine extension
    $extension = ($mimeType === 'image/png') ? 'png' : 'jpg';

    // Save logo
    $logoPath = getVersionLogoPath($qrCodeId, $versionId, $extension);
    $relativeLogoPath = 'logos/' . getVersionLogoFilename($versionId, $extension);

    if (!move_uploaded_file($uploadedFile['tmp_name'], $logoPath)) {
        return [
            'success' => false,
            'path' => null,
            'error' => 'Failed to save logo file'
        ];
    }

    return [
        'success' => true,
        'path' => $relativeLogoPath,
        'error' => null
    ];
}
?>
