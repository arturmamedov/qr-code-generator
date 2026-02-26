-- QR Code Manager Database Schema (SQLite)
-- Execute this SQL file to create the required database structure
-- For SQLite deployments only. For MySQL, use database.sql instead.

-- Table: qr_codes
-- Main QR code entries with metadata
CREATE TABLE IF NOT EXISTS qr_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(33) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    destination_url TEXT NOT NULL,
    click_count INTEGER DEFAULT 0,
    tags VARCHAR(255),
    favorite_version_id INTEGER NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_code ON qr_codes(code);
CREATE INDEX IF NOT EXISTS idx_created_at ON qr_codes(created_at);

-- Table: qr_code_versions
-- Multiple styled versions for each QR code
CREATE TABLE IF NOT EXISTS qr_code_versions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    qr_code_id INTEGER NOT NULL,
    version_name VARCHAR(100) NOT NULL DEFAULT 'Untitled Version',
    style_config TEXT NOT NULL,
    image_filename VARCHAR(255) NOT NULL,
    is_favorite INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Foreign key to qr_codes
    CONSTRAINT fk_version_qr_code
        FOREIGN KEY (qr_code_id)
        REFERENCES qr_codes(id)
        ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_qr_code_id ON qr_code_versions(qr_code_id);
CREATE INDEX IF NOT EXISTS idx_is_favorite ON qr_code_versions(is_favorite);
CREATE INDEX IF NOT EXISTS idx_version_created_at ON qr_code_versions(created_at);

-- Note: The favorite_version_id foreign key constraint on qr_codes is NOT
-- enforced at the database level for SQLite (SQLite cannot add foreign keys
-- after table creation via ALTER TABLE). The application logic handles this
-- relationship. For MySQL, database.sql includes an ALTER TABLE statement.
