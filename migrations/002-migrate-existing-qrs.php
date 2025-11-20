<?php
/**
 * Migration Script: Migrate Existing QR Codes to Versioned System
 *
 * This script:
 * 1. Creates a default version for each existing QR code
 * 2. Moves existing image files to new folder structure
 * 3. Sets favorite_version_id for each QR code
 * 4. Adds foreign key constraint
 *
 * Run this AFTER executing 001-add-qr-versions.sql
 *
 * Usage: php migrations/002-migrate-existing-qrs.php
 */

require_once __DIR__ . '/../includes/init.php';

echo "===========================================\n";
echo "QR Code Versions Migration Script\n";
echo "===========================================\n\n";

// Check if qr_code_versions table exists
$tableCheck = $db->fetchOne("SHOW TABLES LIKE 'qr_code_versions'");
if (!$tableCheck) {
    die("ERROR: qr_code_versions table does not exist. Please run 001-add-qr-versions.sql first.\n");
}

// Get all existing QR codes
$qrCodes = $db->fetchAll("SELECT id, code FROM qr_codes ORDER BY id");

if (empty($qrCodes)) {
    echo "No existing QR codes found. Migration not needed.\n";
    exit(0);
}

echo "Found " . count($qrCodes) . " QR code(s) to migrate...\n\n";

$migrated = 0;
$errors = 0;

foreach ($qrCodes as $qr) {
    $qrId = $qr['id'];
    $code = $qr['code'];

    echo "Migrating QR Code #{$qrId} ({$code})...\n";

    try {
        // Create new folder structure: generated/qr-code-{id}/
        $qrFolder = GENERATED_PATH . '/qr-code-' . $qrId;
        $logosFolder = $qrFolder . '/logos';

        if (!file_exists($qrFolder)) {
            if (!mkdir($qrFolder, 0755, true)) {
                throw new Exception("Failed to create folder: {$qrFolder}");
            }
            echo "  ✓ Created folder: qr-code-{$qrId}/\n";
        }

        if (!file_exists($logosFolder)) {
            if (!mkdir($logosFolder, 0755, true)) {
                throw new Exception("Failed to create logos folder: {$logosFolder}");
            }
            echo "  ✓ Created logos subfolder\n";
        }

        // Move existing image file if it exists
        $oldImagePath = GENERATED_PATH . '/' . $code . '.png';
        $newImagePath = $qrFolder . '/v1.png';

        if (file_exists($oldImagePath)) {
            if (!rename($oldImagePath, $newImagePath)) {
                throw new Exception("Failed to move image file from {$oldImagePath} to {$newImagePath}");
            }
            echo "  ✓ Moved image: {$code}.png → qr-code-{$qrId}/v1.png\n";
        } else {
            echo "  ⚠ Warning: No existing image file found at {$oldImagePath}\n";
            // Create a placeholder - we'll skip this for now as images should exist
        }

        // Create default style config (matching current defaults from create.php)
        $styleConfig = [
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

        $styleConfigJson = json_encode($styleConfig);

        // Insert default version into qr_code_versions
        $versionSql = "INSERT INTO qr_code_versions
                       (qr_code_id, version_name, style_config, image_filename, is_favorite, created_at)
                       VALUES (?, ?, ?, ?, ?, NOW())";

        $versionId = $db->insert($versionSql, "isssi", [
            $qrId,
            'Default Version',
            $styleConfigJson,
            'v1.png',
            1  // is_favorite = true
        ]);

        if (!$versionId) {
            throw new Exception("Failed to create version record");
        }

        echo "  ✓ Created default version (ID: {$versionId})\n";

        // Update qr_codes table with favorite_version_id
        $updateSql = "UPDATE qr_codes SET favorite_version_id = ? WHERE id = ?";
        $updated = $db->execute($updateSql, "ii", [$versionId, $qrId]);

        if ($updated === false) {
            throw new Exception("Failed to set favorite_version_id");
        }

        echo "  ✓ Set as favorite version\n";
        echo "  SUCCESS: QR Code #{$qrId} migrated\n\n";

        $migrated++;

    } catch (Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n\n";
        $errors++;
        logError("Migration error for QR #{$qrId}: " . $e->getMessage(), 'ERROR');
    }
}

echo "===========================================\n";
echo "Migration Summary:\n";
echo "===========================================\n";
echo "Total QR codes: " . count($qrCodes) . "\n";
echo "Successfully migrated: {$migrated}\n";
echo "Errors: {$errors}\n\n";

if ($errors === 0 && $migrated > 0) {
    echo "Adding foreign key constraint for favorite_version_id...\n";
    try {
        $fkSql = "ALTER TABLE qr_codes
                  ADD CONSTRAINT fk_qr_favorite_version
                      FOREIGN KEY (favorite_version_id)
                      REFERENCES qr_code_versions(id)
                      ON DELETE SET NULL";

        $db->execute($fkSql);
        echo "✓ Foreign key constraint added successfully\n\n";
    } catch (Exception $e) {
        echo "⚠ Warning: Could not add foreign key constraint (may already exist)\n";
        echo "  Error: " . $e->getMessage() . "\n\n";
    }
}

if ($errors === 0) {
    echo "✅ Migration completed successfully!\n";
    exit(0);
} else {
    echo "⚠️  Migration completed with errors. Please review the log.\n";
    exit(1);
}
?>
