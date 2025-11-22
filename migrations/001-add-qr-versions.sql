-- Migration: Add QR Code Versions Support
-- Created: 2025-11-20
-- Description: Adds support for multiple styled versions of the same QR code

-- Step 1: Create qr_code_versions table
CREATE TABLE IF NOT EXISTS qr_code_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_code_id INT NOT NULL,
    version_name VARCHAR(100) NOT NULL DEFAULT 'Untitled Version',
    style_config JSON NOT NULL,
    image_filename VARCHAR(255) NOT NULL,
    is_favorite BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign key
    CONSTRAINT fk_version_qr_code
        FOREIGN KEY (qr_code_id)
        REFERENCES qr_codes(id)
        ON DELETE CASCADE,

    -- Indexes
    INDEX idx_qr_code_id (qr_code_id),
    INDEX idx_is_favorite (is_favorite),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 2: Add favorite_version_id column to qr_codes table
ALTER TABLE qr_codes
ADD COLUMN favorite_version_id INT NULL AFTER tags;

-- Step 3: Add foreign key constraint (done after data migration)
-- This will be added by the migration script after creating default versions

-- Note: The PHP migration script (002-migrate-existing-qrs.php) will:
-- 1. For each existing QR code, create a default version
-- 2. Move existing generated/{code}.png files to new folder structure
-- 3. Set the favorite_version_id for each QR code
-- 4. Add the foreign key constraint for favorite_version_id
