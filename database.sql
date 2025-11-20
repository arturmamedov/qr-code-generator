-- QR Code Manager Database Schema
-- Execute this SQL file to create the required database structure

-- Table: qr_codes
-- Main QR code entries with metadata
CREATE TABLE IF NOT EXISTS qr_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(33) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    destination_url TEXT NOT NULL,
    click_count INT DEFAULT 0,
    tags VARCHAR(255),
    favorite_version_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: qr_code_versions
-- Multiple styled versions for each QR code
CREATE TABLE IF NOT EXISTS qr_code_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_code_id INT NOT NULL,
    version_name VARCHAR(100) NOT NULL DEFAULT 'Untitled Version',
    style_config JSON NOT NULL,
    image_filename VARCHAR(255) NOT NULL,
    is_favorite BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign key to qr_codes
    CONSTRAINT fk_version_qr_code
        FOREIGN KEY (qr_code_id)
        REFERENCES qr_codes(id)
        ON DELETE CASCADE,

    -- Indexes
    INDEX idx_qr_code_id (qr_code_id),
    INDEX idx_is_favorite (is_favorite),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraint for favorite_version_id
-- Note: This is added after table creation to avoid circular dependency
ALTER TABLE qr_codes
ADD CONSTRAINT fk_qr_favorite_version
    FOREIGN KEY (favorite_version_id)
    REFERENCES qr_code_versions(id)
    ON DELETE SET NULL;
