# Database Migrations

This folder contains database migration scripts for the QR Code Manager.

## Migration 001 & 002: QR Code Versions Feature

These migrations add support for multiple styled versions of the same QR code.

### What Changes:

**Before:**
- One QR code = One image file
- File structure: `generated/{code}.png`

**After:**
- One QR code = Multiple versions (different styles)
- File structure: `generated/qr-code-{id}/v1.png, v2.png, etc.`
- Logos: `generated/qr-code-{id}/logos/logo_v1.png, logo_v2.png, etc.`

### How to Run:

```bash
# Step 1: Run SQL migration (adds new tables/columns)
mysql -u your_user -p your_database < migrations/001-add-qr-versions.sql

# Step 2: Run PHP migration (migrates existing data)
php migrations/002-migrate-existing-qrs.php
```

### What the Migration Does:

1. **001-add-qr-versions.sql:**
   - Creates `qr_code_versions` table
   - Adds `favorite_version_id` column to `qr_codes` table

2. **002-migrate-existing-qrs.php:**
   - For each existing QR code:
     - Creates folder: `generated/qr-code-{id}/`
     - Creates subfolder: `generated/qr-code-{id}/logos/`
     - Moves image: `{code}.png` → `qr-code-{id}/v1.png`
     - Creates "Default Version" entry in `qr_code_versions`
     - Sets as favorite version
     - Adds foreign key constraint

### Safety:

- ✅ Non-destructive: Original data is preserved
- ✅ Reversible: Files are moved, not deleted
- ✅ Error handling: Stops on errors, logs issues
- ✅ Tested: Verified on development environment

### Rollback:

If you need to rollback (not recommended after use):

```sql
-- Remove foreign key
ALTER TABLE qr_codes DROP FOREIGN KEY fk_qr_favorite_version;

-- Remove column
ALTER TABLE qr_codes DROP COLUMN favorite_version_id;

-- Drop versions table
DROP TABLE qr_code_versions;

-- Manually move files back:
-- qr-code-123/v1.png → ABC123.png (requires custom script)
```

### Troubleshooting:

**Error: "qr_code_versions table does not exist"**
- Run `001-add-qr-versions.sql` first

**Error: "Failed to create folder"**
- Check permissions on `generated/` directory
- Ensure directory is writable by PHP user

**Error: "Failed to move image file"**
- Check if image file exists at old location
- Verify write permissions

**Warning: "No existing image file found"**
- This is normal if QR code was created but image not generated
- Version will be created, but no image file moved

### Verification:

After migration, verify:

```bash
# Check database
mysql> SELECT COUNT(*) FROM qr_code_versions;
mysql> SELECT id, code, favorite_version_id FROM qr_codes;

# Check file structure
ls -la generated/
ls -la generated/qr-code-1/
```

Expected structure:
```
generated/
├── qr-code-1/
│   ├── v1.png
│   └── logos/
├── qr-code-2/
│   ├── v1.png
│   └── logos/
└── ...
```

### Support:

For issues or questions, check:
- `logs/app.log` for error details
- Migration output for specific error messages
- `docs/FEATURE-QR-VERSIONS.md` for feature documentation
