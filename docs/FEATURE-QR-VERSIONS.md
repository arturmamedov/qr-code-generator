# QR Code Versions Feature - Implementation Plan

## 📋 Executive Summary

This document outlines the implementation plan for supporting **multiple styled versions** of the same QR code (same destination URL/slug). Users will be able to create different visual styles (colors, logos, borders) for the same shortened URL, manage them in a gallery, and download any version.

---

## 🎯 Feature Goals

### What Users Want:
1. **Multiple QR Codes per URL**: Create various styled versions for the same destination URL
2. **Same Slug, Different Styles**: All versions share the same code/slug and redirect to the same URL
3. **Visual Management**: View all versions in a gallery/carousel
4. **Favorite/Primary Version**: Mark one version as "favorite" to display in dashboard
5. **Version Management Page**: Dedicated page to manage all versions when there are many
6. **Easy Downloads**: Download any version as PNG/SVG/JPG
7. **Version Control**: Delete old versions, create new ones, track creation dates

### Use Case Example:
> "I create a QR code for `/summer-sale` with a red style for posters. Later, I need a blue version for digital marketing and a green version for business cards. All three QR codes redirect to the same URL, but I can download different styled images for different purposes."

---

## 🏗️ Technical Architecture

### Current System:
```
qr_codes table
├── id (PK)
├── code (unique slug)
├── title
├── description
├── destination_url
├── click_count
├── tags
├── created_at
└── updated_at

File Storage: generated/{code}.png (ONE image per code)
```

### Proposed Architecture:

```
qr_codes table (MODIFIED)
├── id (PK)
├── code (unique slug)
├── title
├── description
├── destination_url
├── click_count
├── tags
├── favorite_version_id (FK → qr_code_versions.id, nullable)
├── created_at
└── updated_at

qr_code_versions table (NEW)
├── id (PK)
├── qr_code_id (FK → qr_codes.id)
├── version_name (e.g., "Red Print Version", "Blue Digital")
├── style_config (JSON: all qr-code-styling options)
├── image_filename (e.g., "ABC123_v1.png")
├── is_favorite (boolean, redundant but useful for queries)
├── created_at
└── updated_at

File Storage: generated/{code}_v{version_id}.png (MULTIPLE images per code)
```

---

## 📊 Database Changes

### 1. New Table: `qr_code_versions`

```sql
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
```

### 2. Modify Existing Table: `qr_codes`

```sql
ALTER TABLE qr_codes
ADD COLUMN favorite_version_id INT NULL,
ADD CONSTRAINT fk_qr_favorite_version
    FOREIGN KEY (favorite_version_id)
    REFERENCES qr_code_versions(id)
    ON DELETE SET NULL;
```

### 3. Migration Strategy

**For Existing QR Codes:**
- Create a migration script to convert existing QR codes to versioned system
- For each existing QR code:
  - Create a "Default Version" entry in `qr_code_versions`
  - Rename image file from `{code}.png` → `{code}_v1.png`
  - Set as favorite version
  - Preserve all original data

**Migration Script Pseudocode:**
```php
// For each existing QR code:
1. Get QR code data from qr_codes table
2. Create default style_config JSON (current defaults)
3. Insert version record into qr_code_versions
4. Rename physical file: generated/{code}.png → generated/{code}_v{id}.png
5. Update qr_codes.favorite_version_id to new version ID
```

---

## 🎨 Style Configuration (JSON)

The `style_config` field will store all qr-code-styling library options:

```json
{
    "width": 300,
    "height": 300,
    "margin": 10,
    "dotsOptions": {
        "type": "rounded",
        "color": "#000000"
    },
    "backgroundOptions": {
        "color": "#ffffff"
    },
    "cornersSquareOptions": {
        "type": "extra-rounded",
        "color": "#000000"
    },
    "cornersDotOptions": {
        "type": "square",
        "color": "#000000"
    },
    "imageOptions": {
        "hideBackgroundDots": true,
        "imageSize": 0.4,
        "margin": 0
    },
    "logo": {
        "hasLogo": true,
        "logoPath": "logos/logo_v1.png",
        "logoFilename": "company-logo.png"
    }
}
```

**Benefits:**
- ✅ Complete style preservation
- ✅ Easy to regenerate QR codes from config
- ✅ Version comparison/cloning
- ✅ Future-proof for new styling options

---

## 🔄 User Flows

### Flow 1: Create New QR Code (Modified)
```
1. User fills form on create.php
2. User customizes QR styling
3. Clicks "Generate QR Code"
   → Backend creates entry in qr_codes
   → Backend creates FIRST version in qr_code_versions
   → Sets as favorite version
   → Saves image as generated/{code}_v{id}.png
4. Success! Redirect to edit page
```

### Flow 2: Create New Version (NEW)
```
1. User on edit.php for existing QR code
2. Clicks "Create New Version" button
3. Modal/Form opens with style options
   → Can clone current version or start fresh
   → Can name the version (e.g., "Blue Print Version")
4. User customizes styling
5. Clicks "Save Version"
   → Backend creates new version entry
   → Generates & saves new image
6. Gallery updates with new version
```

### Flow 3: View All Versions (Modified)
```
Dashboard (index.php):
- Shows ONLY favorite version thumbnail

Edit Page (edit.php):
- Shows current/favorite version preview
- Below: Version gallery with thumbnails
  → If <= 5 versions: Show all in carousel
  → If > 5 versions: Show first 5 + "View All Versions" button

Versions Management Page (versions.php - NEW):
- Dedicated page showing all versions for this QR code
- Grid layout with pagination (12 per page)
- Each version shows: thumbnail, name, created date
- Actions: Set Favorite, Download, Delete, Clone
```

### Flow 4: Set Favorite Version (NEW)
```
1. User clicks "Set as Favorite" on any version
2. Backend updates:
   → qr_code_versions: Set is_favorite=true for selected
   → qr_code_versions: Set is_favorite=false for others
   → qr_codes: Update favorite_version_id
3. Dashboard updates to show new favorite
```

### Flow 5: Delete Version (NEW)
```
1. User clicks "Delete" on a version
2. System checks:
   → Must have at least 2 versions (can't delete last one)
   → If deleting favorite, prompt to select new favorite
3. Confirmation modal
4. Backend:
   → Deletes version record from DB
   → Deletes physical image file
   → If was favorite, unset qr_codes.favorite_version_id
```

---

## 📁 File Structure Changes

### New Files to Create:
```
/versions.php              # Dedicated versions management page
/api-versions.php          # API endpoints for version CRUD
/includes/version-helpers.php  # Helper functions for versions
/docs/FEATURE-QR-VERSIONS.md   # This document
/migrations/001-add-versions.sql  # Database migration
```

### Files to Modify:
```
/database.sql              # Add new table schema
/api.php                   # Update create/update handlers
/create.php                # Minor UI changes
/edit.php                  # Major UI additions (version gallery)
/index.php                 # Show favorite version only
/save-image.php            # Support versioned filenames
/assets/style.css          # Styles for version gallery
/assets/app.js             # JavaScript for version management
/includes/helpers.php      # Add version-related helpers
```

---

## 🔧 API Endpoints (New & Modified)

### New Endpoints (api-versions.php):

```php
POST /api-versions.php
{
    "action": "create_version",
    "qr_code_id": 123,
    "version_name": "Blue Print Version",
    "style_config": { ... }
}

POST /api-versions.php
{
    "action": "get_versions",
    "qr_code_id": 123,
    "page": 1,
    "limit": 12
}

POST /api-versions.php
{
    "action": "set_favorite",
    "version_id": 456
}

POST /api-versions.php
{
    "action": "delete_version",
    "version_id": 456
}

POST /api-versions.php
{
    "action": "update_version",
    "version_id": 456,
    "version_name": "Updated Name",
    "style_config": { ... }
}

POST /api-versions.php
{
    "action": "clone_version",
    "version_id": 456,
    "new_name": "Copy of Blue Version"
}
```

### Modified Endpoints (api.php):

```php
// api.php - handleCreate()
// NOW creates both qr_codes entry AND first version

// api.php - handleUpdate()
// NOW allows updating metadata without affecting versions

// api.php - handleDelete()
// NOW cascades delete to all versions (foreign key handles it)

// api.php - handleGetSingle()
// NOW returns favorite_version_id and version count
```

---

## 🎨 UI/UX Design

### Dashboard (index.php)
```
┌──────────────────────────────────────────┐
│ Last Created QR Codes                    │
├──────────────────────────────────────────┤
│ ┌─────────┐  Title: Summer Sale         │
│ │  [QR]   │  Code: summer-sale           │
│ │ IMAGE   │  Clicks: 156                 │
│ │(FAV)    │  Versions: 3                 │
│ └─────────┘  [Edit] [Download]           │
└──────────────────────────────────────────┘
```
- Shows ONLY favorite version thumbnail
- Displays "Versions: X" count
- Click "Edit" goes to edit page with all versions

### Edit Page (edit.php) - Version Gallery Section
```
┌──────────────────────────────────────────┐
│ QR Code Versions                    [+]  │
├──────────────────────────────────────────┤
│ ┌─────┐  ┌─────┐  ┌─────┐  ┌─────┐     │
│ │ QR1 │  │ QR2 │  │ QR3 │  │ QR4 │     │
│ │⭐FAV│  │     │  │     │  │     │     │
│ └─────┘  └─────┘  └─────┘  └─────┘     │
│ Red Ver  Blue V.  Green V  Print V      │
│ [Set ⭐] [Set ⭐] [Set ⭐] [Set ⭐]      │
│ [📥][🗑️] [📥][🗑️] [📥][🗑️] [📥][🗑️]  │
│                                          │
│ [View All 8 Versions →]                 │
└──────────────────────────────────────────┘
```
- Shows up to 5 versions in carousel
- Favorite marked with ⭐
- Quick actions: Set Favorite, Download, Delete
- "View All" button if > 5 versions
- "+ Create New Version" button

### Versions Management Page (versions.php - NEW)
```
┌──────────────────────────────────────────┐
│ ← Back to Edit                           │
│ Manage Versions: summer-sale             │
├──────────────────────────────────────────┤
│ [+ Create New Version]                   │
│                                          │
│ ┌─────────┐ ┌─────────┐ ┌─────────┐    │
│ │   QR1   │ │   QR2   │ │   QR3   │    │
│ │  ⭐FAV  │ │         │ │         │    │
│ └─────────┘ └─────────┘ └─────────┘    │
│ Red Print   Blue Web    Green Card      │
│ Jan 5, 2025 Jan 8, 2025 Jan 10, 2025   │
│ [Set ⭐] [Edit] [Clone] [📥] [🗑️]      │
│                                          │
│ [1] 2 3 ... 10  (Pagination)            │
└──────────────────────────────────────────┘
```
- Grid layout (3-4 columns)
- Pagination (12 versions per page)
- Full actions per version
- Search/filter by name (future)

### Create Version Modal
```
┌──────────────────────────────────────────┐
│ Create New QR Version                 [×]│
├──────────────────────────────────────────┤
│ Version Name:                            │
│ [Blue Print Version____________]         │
│                                          │
│ Start From:                              │
│ ○ Clone current version                 │
│ ● Start with default style              │
│                                          │
│ [Styling Options...]                     │
│ - Size, colors, dots, corners, logo      │
│ - Live preview on right →                │
│                                          │
│ [Cancel] [Create Version]                │
└──────────────────────────────────────────┘
```

---

## 📝 Implementation Phases

### Phase 1: Database & Backend Foundation (Day 1)
- [ ] Create database migration script
- [ ] Add `qr_code_versions` table
- [ ] Modify `qr_codes` table (add favorite_version_id)
- [ ] Create migration script for existing QR codes
- [ ] Test database structure
- [ ] Create `/includes/version-helpers.php` with core functions
- [ ] Update `/includes/helpers.php` with version utilities

### Phase 2: API Endpoints (Day 1-2)
- [ ] Create `/api-versions.php` with all CRUD endpoints:
  - `create_version`
  - `get_versions`
  - `set_favorite`
  - `delete_version`
  - `update_version`
  - `clone_version`
- [ ] Modify `/api.php` create handler to support versions
- [ ] Modify `/api.php` update handler (don't affect versions)
- [ ] Modify `/save-image.php` to support versioned filenames
- [ ] Test all API endpoints

### Phase 3: Create Flow Modification (Day 2)
- [ ] Update `/create.php` form submission logic
- [ ] Create first version automatically on QR creation
- [ ] Update JavaScript to handle version creation
- [ ] Test create flow end-to-end

### Phase 4: Edit Page - Version Gallery (Day 3)
- [ ] Add version gallery section to `/edit.php`
- [ ] Display up to 5 versions in carousel
- [ ] Show "View All" link if > 5 versions
- [ ] Add "Create New Version" button
- [ ] Implement modal for creating new version
- [ ] Add quick actions (set favorite, download, delete)
- [ ] Style version gallery in `/assets/style.css`
- [ ] Add JavaScript for version management UI
- [ ] Test edit page with multiple versions

### Phase 5: Versions Management Page (Day 4)
- [ ] Create `/versions.php` page
- [ ] Implement grid layout with pagination
- [ ] Display all versions for a QR code
- [ ] Add full action buttons per version
- [ ] Implement pagination logic (12 per page)
- [ ] Style versions page
- [ ] Add JavaScript for interactions
- [ ] Test pagination and actions

### Phase 6: Dashboard Updates (Day 4)
- [ ] Update `/index.php` to show favorite version only
- [ ] Display version count per QR code
- [ ] Update "Last Created" section
- [ ] Ensure thumbnails link correctly
- [ ] Test dashboard display

### Phase 7: File Management (Day 5)
- [ ] Update image save logic for versioned filenames
- [ ] Implement image deletion when version deleted
- [ ] Handle logo uploads per version
- [ ] Test file storage and cleanup
- [ ] Verify no orphaned files

### Phase 8: Polish & Testing (Day 5-6)
- [ ] Cross-browser testing
- [ ] Mobile responsiveness
- [ ] Error handling and validation
- [ ] Loading states and animations
- [ ] Accessibility improvements
- [ ] Documentation updates
- [ ] User testing

---

## 🚨 Important Considerations

### 1. **File Storage Strategy**
**Current:** `generated/{code}.png`
**Proposed:** `generated/qr-code-{id}/` (folder per QR code)

**Folder Structure:**
```
generated/
└── qr-code-123/              (based on QR ID, never changes)
    ├── v1.png                (version 1 QR image)
    ├── v2.png                (version 2 QR image)
    ├── v3.png                (version 3 QR image)
    └── logos/                (logos subfolder)
        ├── logo_v1.png       (version 1 logo, if exists)
        └── logo_v2.png       (version 2 logo, if exists)
```

**Why folder per QR code (using ID)?**
- ✅ ID never changes (unlike slug which can be edited)
- ✅ All versions grouped together in one folder
- ✅ Easy to manage logos per version
- ✅ No folder renaming when slug changes
- ✅ Clean organization and cleanup

**Logo Handling:**
- Logos uploaded to `generated/qr-code-{id}/logos/logo_v{version_id}.png`
- If version has no logo, no logo file is created
- Logo path stored in style_config JSON

**Cleanup:**
- Delete version → Delete QR image + logo file (if exists)
- Delete QR code → Delete entire `qr-code-{id}` folder with all versions + logos

### 2. **Favorite Version Logic**
- Each QR code must have a favorite version
- When creating first version, auto-set as favorite
- When deleting favorite version, prompt user to select new favorite
- Dashboard and index always show favorite version

### 3. **Version Limits**
**Recommendation:** Limit to 20 versions per QR code
- Prevents database bloat
- Keeps UI manageable
- Most users won't need more than 5-10

**Implementation:**
- Check count before allowing "Create New Version"
- Show warning at 15 versions
- Hard limit at 20 versions

### 4. **Logo Handling**
**Current:** Logo uploaded per QR code, stored temporarily
**Proposed:** Logo uploaded and saved to `generated/qr-code-{id}/logos/` folder

**Why folder storage?**
- Each version can have different logo
- Clean file organization
- Easy backup and migration
- No database bloat from base64

**Process:**
1. User uploads logo → Save to `generated/qr-code-{id}/logos/logo_v{version_id}.png`
2. Store logo path/filename in style_config JSON
3. When regenerating, load logo from file path
4. If no logo: no file created, logo field null in style_config

### 5. **Click Tracking**
- Keep clicks at QR code level (shared across all versions)
- All versions redirect through same slug
- Click counter increments regardless of which version was scanned

### 6. **Slug Changes**
- When slug is changed, ALL versions remain associated
- Image filenames use version_id, not slug
- No file renaming needed on slug change

### 7. **Backward Compatibility**
- Migration script handles existing QR codes
- Old API calls still work (create one version by default)
- No breaking changes for existing functionality

---

## 🎯 Success Metrics

After implementation, users should be able to:
- ✅ Create multiple styled versions of one QR code
- ✅ See all versions in a manageable gallery
- ✅ Mark one version as favorite for dashboard display
- ✅ Download any version at any time
- ✅ Delete old versions safely
- ✅ Clone existing versions to create variations
- ✅ Manage versions through dedicated page when needed

---

## ❓ Questions for Review

Please review this plan and confirm:

1. **Does this match your vision?** Any features missing or unwanted?

2. **Version limit of 20** - Is this reasonable? Should it be higher/lower?

3. **Version naming** - Should version names be:
   - Optional with auto-names like "Version 1", "Version 2"?
   - Or required with user-provided names?

4. **Default version** - When creating a QR code, should we:
   - Create one version immediately?
   - Or let users create versions afterward?

5. **Logo storage** - ✅ CONFIRMED: Upload to folders (`qr-code-{id}/logos/`)

6. **UI preference** - For edit page with many versions:
   - Show first 5 + "View All" button?
   - Or always show first 10 in scrollable carousel?

7. **Dashboard display** - Should dashboard show:
   - Only favorite version? (recommended)
   - Or latest created version?
   - Or give user the choice?

8. **Implementation timeline** - Does 5-6 days sound reasonable for this feature?

---

## 📚 Additional Notes

### Dependencies:
- ✅ qr-code-styling library (already in use)
- ✅ MySQL 5.7+ (for JSON column support)
- ✅ PHP 7.4+ (for json functions)

### No Breaking Changes:
- Existing QR codes migrate automatically
- All current features remain functional
- API backward compatible

### Future Enhancements (Post-MVP):
- Version comparison view
- Bulk version operations
- Version templates/presets
- A/B testing between versions
- Analytics per version

---

**Status:** 🟡 Awaiting review and approval
**Next Step:** User feedback and plan refinement
