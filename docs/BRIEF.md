## 🎯 PROMPT FOR CLAUDE CODE (Ultra Thinking + Step-by-step Mode)

```
# QR Code Manager - Self-Hosted Application

Create a complete QR code management system for deployment on qr.nestshostels.com with the following specifications:

## PROJECT OVERVIEW
Build a self-hosted QR code manager with dynamic redirect capabilities. Users can create QR codes that point to short URLs (qr.nestshostels.com/XXXX) which redirect to any destination URL. The destination can be changed anytime without regenerating the QR code.

## TECHNICAL STACK
- **Backend:** PHP 8+ with MySQLi
- **Frontend:** Vanilla JavaScript + qr-code-styling library (https://github.com/kozakdenys/qr-code-styling)
- **Database:** MySQL
- **Styling:** Custom minimal CSS (clean, functional, responsive)
- **Deployment:** FTP upload to root directory
- **Security:** .htaccess for authentication and API protection

## CORE ARCHITECTURE

### 1. File Structure
```
/
├── index.php              # Admin dashboard (QR list + stats)
├── create.php             # QR creation form with live preview
├── edit.php?id=X          # QR edit page
├── api.php                # Backend API endpoint
├── r.php?c=XXXX          # Public redirect handler
├── config.php             # Database credentials
├── .htaccess              # Security + rewrite rules
├── /assets/
│   ├── style.css
│   ├── app.js
│   └── qr-code-styling.js (local copy or CDN)
└── /generated/            # Saved QR code images
```

### 2. Database Schema
```sql
CREATE TABLE qr_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) UNIQUE NOT NULL,
    title VARCHAR(255),
    description TEXT,
    destination_url TEXT NOT NULL,
    click_count INT DEFAULT 0,
    tags VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(code)
);
```

### 3. Security (.htaccess)
- Protect admin pages (index.php, create.php, edit.php, api.php) with HTTP Basic Auth
- Allow public access to r.php (redirect handler)
- Clean URL rewriting: /ABC123 → /r.php?c=ABC123
- Prevent direct access to config.php

### 4. Key Features

**Admin Dashboard (index.php):**
- Table view of all QR codes with: thumbnail preview, title, short code, destination URL, click count, created date
- Actions: Edit button, Delete button, Download QR button
- "Create New QR" prominent button → create.php
- Total stats at top: Total QR codes, Total clicks

**Create Page (create.php):**
- Form fields:
  - Title (required)
  - Description (optional textarea)
  - Destination URL (required, with validation)
  - Tags (optional, comma-separated)
  - QR Style options (using qr-code-styling):
    * Size (default 300x300)
    * Dot style (square, dots, rounded)
    * Color picker for dots
    * Background color
- Live preview canvas showing QR as user types/changes settings
- Generate button → saves to DB, generates image file, shows success with download options
- Auto-generate unique 6-character alphanumeric code

**Edit Page (edit.php):**
- Same form as create.php but pre-filled
- Update button instead of Generate
- Show current click count (read-only)
- Option to reset click counter

**Redirect Handler (r.php):**
- Extract code from query parameter
- Query DB for destination_url
- Increment click_count
- 301 redirect to destination
- If code not found: show friendly 404 with link back to main site

**API Endpoint (api.php):**
- JSON responses
- Actions: create, update, delete, get_all, get_single
- Input validation and sanitization
- Return success/error messages

### 5. QR Code Generation
- Use qr-code-styling library client-side
- Generate QR pointing to: https://qr.nestshostels.com/r.php?c=ABC123
- Save generated image to /generated/ folder with filename: {code}.png
- Support download in PNG, SVG, JPEG formats

### 6. Configuration
**config.php template:**
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('BASE_URL', 'https://qr.nestshostels.com');
?>
```

### 7. Code Quality Requirements
- Follow DRY and SOLID principles
- Pragmatic approach - no over-engineering
- Clear, maintainable code with inline comments
- Prepared statements for all SQL queries (prevent injection)
- Input validation and sanitization
- Error handling with user-friendly messages
- Responsive design (mobile-friendly tables/forms)

## STEP-BY-STEP IMPLEMENTATION PLAN

**Phase 1: Foundation**
1. Create database schema and config.php template
2. Build .htaccess with security rules and URL rewriting
3. Setup basic file structure

**Phase 2: Backend Logic**
4. Implement api.php with all CRUD operations
5. Build r.php redirect handler with click tracking
6. Create database helper functions

**Phase 3: Admin Interface**
7. Build index.php dashboard with QR list and stats
8. Implement create.php with form and qr-code-styling integration
9. Build edit.php page
10. Add delete functionality

**Phase 4: Polish**
11. Style all pages with minimal custom CSS
12. Add JavaScript for live preview and form interactions
13. Implement download functionality for different formats
14. Test all features and edge cases

**Phase 5: Documentation**
15. Create README.md with:
    - Installation instructions
    - .htaccess password setup guide
    - Database setup SQL
    - config.php configuration
    - FTP upload checklist

## ADDITIONAL REQUIREMENTS
- All user inputs must be validated and sanitized
- Use meaningful variable names (English)
- Add TODO comments for future enhancements
- Keep it simple - avoid unnecessary abstractions
- Make it easy to customize colors/styles in CSS
- Ensure generated/ directory is writable (instructions in README)

## EXPECTED DELIVERABLES
- Complete, working codebase ready for FTP upload
- Installation README with clear steps
- SQL file for database creation
- Comments explaining key logic sections

Please implement this step-by-step, showing your planning and thinking process at each phase. Ask clarifying questions if anything is ambiguous before proceeding with code generation.
```
