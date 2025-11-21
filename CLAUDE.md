# CLAUDE.md - AI Assistant Guide for QR Code Manager

This document provides comprehensive guidance for AI assistants (like Claude) working on the QR Code Manager codebase.

## 📋 Table of Contents

1. [Project Overview](#project-overview)
2. [Architecture](#architecture)
3. [Technology Stack](#technology-stack)
4. [File Structure](#file-structure)
5. [Database Schema](#database-schema)
6. [Key Components](#key-components)
7. [Development Workflows](#development-workflows)
8. [Coding Conventions](#coding-conventions)
9. [Common Tasks](#common-tasks)
10. [Security Guidelines](#security-guidelines)
11. [Testing & Debugging](#testing--debugging)
12. [Git Workflow](#git-workflow)
13. [Deployment](#deployment)

---

## 🎯 Project Overview

**QR Code Manager** is a self-hosted PHP application for creating and managing dynamic QR codes with redirect capabilities. It allows users to create QR codes that point to short URLs (e.g., `qr.nestshostels.com/summer-sale`) which redirect to any destination URL. The key feature is that destination URLs can be changed anytime without regenerating the QR code.

**Primary Use Case:** Print QR codes on physical materials (menus, posters, products) and update their destinations dynamically without reprinting.

**Target Environment:**
- Traditional PHP shared hosting (FTP deployment)
- Apache web server with mod_rewrite
- MySQL/MariaDB database
- No build process or package managers required

---

## 🏗️ Architecture

### Design Philosophy

This project follows a **pragmatic, traditional PHP architecture**:
- **No frameworks** - Pure PHP with procedural and OOP hybrid approach
- **Server-side rendering** - PHP generates HTML with embedded data
- **Vanilla JavaScript** - No frontend framework, minimal JS for interactivity
- **Single-file pages** - Each page is a complete, self-contained PHP file
- **Shared utilities** - Common code in `/includes/` directory

### Request Flow

1. **Admin Pages** (index.php, create.php, edit.php):
   - User → Apache `.htaccess` → HTTP Basic Auth → PHP page → HTML response

2. **API Requests** (api.php):
   - JavaScript → POST to api.php → JSON response → JavaScript handles UI update

3. **Public Redirects** (r.php):
   - User scans QR → Clean URL (/.htaccess rewrite) → r.php → Database lookup → 301 redirect

### Pattern: Singleton Database

The `Database` class uses the Singleton pattern to ensure only one database connection exists per request:

```php
$db = Database::getInstance();
```

**Why?** Prevents multiple connections, simplifies access throughout the app.

---

## 🛠️ Technology Stack

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| **Backend** | PHP | 8.0+ | Server-side logic |
| **Database** | MySQL/MariaDB | 5.7+/10.2+ | Data persistence |
| **Database Driver** | MySQLi | Native | DB operations with prepared statements |
| **Web Server** | Apache | 2.4+ | HTTP server with mod_rewrite |
| **Frontend** | Vanilla JavaScript | ES6+ | Client-side interactivity |
| **CSS** | Custom CSS | CSS3 | Styling with CSS variables |
| **QR Library** | qr-code-styling | Latest | QR code generation (client-side) |
| **Security** | .htaccess | Apache | HTTP Basic Auth + access control |

**No Build Tools:** Files are deployed directly via FTP without transpilation or bundling.

---

## 📁 File Structure

```
/
├── index.php              # Admin dashboard (list all QR codes)
├── create.php             # Create new QR code form
├── edit.php               # Edit existing QR code
├── api.php                # REST-like API endpoint (CRUD operations)
├── r.php                  # Public redirect handler (tracks clicks)
├── save-image.php         # Handle logo image uploads
├── diagnostic.php         # Server diagnostics tool
│
├── config.php             # ⚠️ DO NOT COMMIT - Database credentials
├── config.example.php     # Template for config.php
├── database.sql           # Database schema
├── .htaccess              # Apache config (security + URL rewriting)
├── .htpasswd              # ⚠️ DO NOT COMMIT - Password hashes
├── .gitignore             # Git exclusions
├── README.md              # User documentation
│
├── /includes/
│   ├── init.php           # Bootstrap file (loads everything)
│   ├── Database.php       # Database singleton class
│   └── helpers.php        # Utility functions
│
├── /assets/
│   ├── style.css          # Main stylesheet
│   └── app.js             # JavaScript for all pages
│
├── /generated/            # ⚠️ Writable - Saved QR code images
│   └── .gitkeep
│
├── /logs/                 # ⚠️ Writable - Error logs
│   └── .gitkeep
│
└── /docs/
    └── BRIEF.md           # Original project brief
```

### Important Notes

- **Never commit:** `config.php`, `.htpasswd`, generated images, logs
- **Must be writable:** `/generated/`, `/logs/`
- **Protected by .htaccess:** `config.php`, `database.sql`, `.git/`, `/logs/`

---

## 🗄️ Database Schema

### Table: `qr_codes`

```sql
CREATE TABLE qr_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(33) UNIQUE NOT NULL,      -- Unique slug/code (6-33 chars)
    title VARCHAR(255) NOT NULL,           -- Display name
    description TEXT,                      -- Optional description
    destination_url TEXT NOT NULL,         -- Target redirect URL
    click_count INT DEFAULT 0,             -- Tracking counter
    tags VARCHAR(255),                     -- Comma-separated tags
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),                 -- Fast lookup for redirects
    INDEX idx_created_at (created_at)      -- Sorting in dashboard
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Key Fields

- **`code`**: The URL slug (e.g., "ABC123" or "summer-sale")
  - Auto-generated: 6 uppercase alphanumeric chars (e.g., "A3K9M2")
  - Custom: User-defined slug with letters, numbers, hyphens, underscores (1-33 chars)
  - Must be unique across the table
  - Case-sensitive (preserved as entered)

- **`destination_url`**: Where the QR code redirects
  - Must be valid HTTP/HTTPS URL
  - Can be updated anytime

- **`click_count`**: Incremented each time someone scans the QR code

---

## 🔑 Key Components

### 1. `/includes/init.php` - Application Bootstrap

**Purpose:** Single file to include in every PHP page to initialize the app.

**What it does:**
- Starts PHP session
- Loads `config.php`
- Loads `Database.php` class
- Loads `helpers.php` functions
- Sets up error handling and logging
- Initializes database connection
- Creates required directories

**Usage:**
```php
require_once __DIR__ . '/includes/init.php';
// Now you have access to $db and all helper functions
```

### 2. `/includes/Database.php` - Database Wrapper

**Pattern:** Singleton with mysqli prepared statements

**Key Methods:**

```php
// Get instance
$db = Database::getInstance();

// Insert (returns last insert ID)
$id = $db->insert($sql, $types, $params);

// Update/Delete (returns affected rows)
$affected = $db->execute($sql, $types, $params);

// Fetch single row
$row = $db->fetchOne($sql, $types, $params);

// Fetch all rows
$rows = $db->fetchAll($sql, $types, $params);

// Escape string (when needed)
$safe = $db->escape($value);
```

**Important:** Always use prepared statements with type strings:
- `"s"` = string
- `"i"` = integer
- `"d"` = double
- `"b"` = blob

Example:
```php
$db->fetchOne(
    "SELECT * FROM qr_codes WHERE code = ? AND click_count > ?",
    "si",
    [$code, $minClicks]
);
```

### 3. `/includes/helpers.php` - Utility Functions

**Critical Functions:**

| Function | Purpose | Example |
|----------|---------|---------|
| `generateUniqueCode($length, $maxAttempts, $customSlug)` | Generate or validate unique QR code | `generateUniqueCode(6)` |
| `isValidSlug($slug)` | Validate custom slug format | Returns `['valid' => bool, 'error' => string]` |
| `isValidUrl($url)` | Validate destination URL | Checks format and scheme |
| `sanitizeInput($input)` | Sanitize user input | Use before displaying |
| `sanitizeUrl($url)` | Sanitize URLs | Use before saving |
| `jsonResponse($success, $data, $message, $statusCode)` | Send JSON response | For API endpoints |
| `redirect($url, $statusCode)` | Redirect user | For r.php |
| `logError($message, $level)` | Log errors to file | Automatic via error handler |
| `getQrUrl($code)` | Get full QR URL | `qr.nestshostels.com/ABC123` |

### 4. `api.php` - API Endpoint

**Actions:** (POST only, expects JSON)

- `create` - Create new QR code
- `update` - Update existing QR code
- `delete` - Delete QR code
- `get_all` - Fetch all QR codes
- `get_single` - Fetch one QR code by ID
- `reset_clicks` - Reset click counter
- `check_slug_availability` - Check if slug is available

**Request Format:**
```javascript
fetch('/api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'create',
        title: 'My QR Code',
        destination_url: 'https://example.com',
        custom_slug: 'my-qr'  // optional
    })
})
```

**Response Format:**
```json
{
    "success": true,
    "message": "QR code created successfully",
    "data": {
        "id": 123,
        "code": "my-qr"
    }
}
```

### 5. `r.php` - Redirect Handler

**Flow:**
1. Extract code from `?c=` parameter (populated by .htaccess rewrite)
2. Validate code format (1-33 chars, alphanumeric + hyphens + underscores)
3. Query database for destination URL
4. Increment click counter
5. 301 redirect to destination
6. If not found: Show friendly 404 page

**URL Patterns:**
- User types: `qr.nestshostels.com/summer-sale`
- `.htaccess` rewrites to: `qr.nestshostels.com/r.php?c=summer-sale`
- PHP handles: `$_GET['c']` = "summer-sale"

### 6. `.htaccess` - Apache Configuration

**Three Main Sections:**

1. **Security:**
   - Block direct access to: `config.php`, `.git/`, `database.sql`, `/logs/`
   - Protect admin pages with HTTP Basic Auth

2. **URL Rewriting:**
   - Pattern: `^([A-Za-z0-9_-]{1,33})$`
   - Rewrite `/ABC123` → `/r.php?c=ABC123`
   - Skip rewrites for existing files/directories

3. **Performance:**
   - GZIP compression
   - Browser caching for assets
   - Security headers (X-Frame-Options, etc.)

### 7. Frontend JavaScript (`assets/app.js`)

**Key Features:**
- QR code preview using `qr-code-styling` library
- Real-time slug validation
- Auto-generate random slugs
- Form validation
- AJAX API calls
- Search/filter/sort for dashboard
- Download QR codes in multiple formats

**Pattern:** Vanilla JavaScript with event delegation and modular functions.

---

## 🔄 Development Workflows

### Adding a New Feature

1. **Database Changes:**
   - Add columns to `database.sql`
   - Document in this file's schema section
   - Create migration notes in commit message

2. **Backend Logic:**
   - Add helper functions to `helpers.php` if reusable
   - Add API actions to `api.php` if needed
   - Update `Database.php` if new query patterns needed

3. **Frontend:**
   - Update HTML in PHP files
   - Add JavaScript in `app.js`
   - Add CSS in `style.css`

4. **Testing:**
   - Test locally on PHP built-in server: `php -S localhost:8000`
   - Test authentication flow
   - Test database operations
   - Test edge cases

5. **Documentation:**
   - Update README.md for user-facing changes
   - Update this CLAUDE.md for developer guidance
   - Add code comments for complex logic

### Modifying Existing Features

**Before changing code:**

1. **Search for dependencies:**
   ```bash
   grep -r "functionName" .
   grep -r "tableName" .
   ```

2. **Check database usage:**
   - Look for queries using the affected table/column
   - Ensure backward compatibility or document breaking changes

3. **Test authentication:**
   - Verify .htaccess still protects admin pages
   - Check that r.php remains public

4. **Validate input/output:**
   - Ensure all user input is sanitized
   - Check for SQL injection vulnerabilities
   - Verify XSS protection

---

## 📝 Coding Conventions

### PHP Conventions

**File Structure:**
```php
<?php
/**
 * File Purpose
 *
 * Detailed description of what this file does
 */

require_once __DIR__ . '/includes/init.php';

// Main logic here

?>
```

**Naming:**
- **Variables:** `$camelCase` (e.g., `$destinationUrl`)
- **Functions:** `camelCase()` (e.g., `generateUniqueCode()`)
- **Constants:** `UPPER_CASE` (e.g., `DB_HOST`)
- **Classes:** `PascalCase` (e.g., `Database`)
- **Database tables:** `snake_case` (e.g., `qr_codes`)
- **Database columns:** `snake_case` (e.g., `destination_url`)

**SQL Queries:**
- Always use prepared statements
- Never concatenate user input into queries
- Format for readability:
```php
$qrCode = $db->fetchOne(
    "SELECT id, code, destination_url
     FROM qr_codes
     WHERE code = ? AND click_count > ?
     LIMIT 1",
    "si",
    [$code, $minClicks]
);
```

**Error Handling:**
```php
try {
    // Database operations
} catch (Exception $e) {
    logError("Error description: " . $e->getMessage());
    // Handle gracefully
}
```

### JavaScript Conventions

**Naming:**
- **Variables:** `camelCase`
- **Constants:** `UPPER_CASE`
- **Functions:** `camelCase`

**Async Operations:**
```javascript
async function createQrCode(data) {
    try {
        const response = await fetch('/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error:', error);
        showError('Operation failed');
    }
}
```

### CSS Conventions

**Use CSS Variables:**
```css
:root {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
    --text-color: #333;
    --bg-color: #f5f7fa;
}
```

**BEM-like Naming:**
```css
.stat-card { }
.stat-card__header { }
.stat-card--highlighted { }
```

**Mobile-First Responsive:**
```css
.container {
    width: 100%;
}

@media (min-width: 768px) {
    .container {
        max-width: 1200px;
    }
}
```

---

## 🔧 Common Tasks

### Task 1: Add a New Field to QR Codes

**Example: Add "campaign_name" field**

1. **Update database:**
```sql
ALTER TABLE qr_codes ADD COLUMN campaign_name VARCHAR(100) AFTER title;
```

2. **Update `database.sql`:**
```sql
campaign_name VARCHAR(100),
```

3. **Update API (`api.php`):**
```php
// In handleCreate() and handleUpdate()
$campaignName = sanitizeInput($input['campaign_name'] ?? '');

// Add to INSERT query
"INSERT INTO qr_codes (code, title, campaign_name, ...)"
```

4. **Update forms (`create.php`, `edit.php`):**
```php
<div class="form-group">
    <label for="campaign_name">Campaign Name</label>
    <input type="text" id="campaign_name" name="campaign_name"
           value="<?php echo $qrCode['campaign_name'] ?? ''; ?>">
</div>
```

5. **Update dashboard (`index.php`):**
```php
<td><?php echo htmlspecialchars($qr['campaign_name']); ?></td>
```

### Task 2: Add a New API Action

**Example: Add "duplicate" action**

1. **Add to `api.php`:**
```php
case 'duplicate':
    handleDuplicate($input);
    break;
```

2. **Implement handler:**
```php
function handleDuplicate($input) {
    global $db;

    // Validate input
    if (empty($input['id'])) {
        jsonResponse(false, null, 'ID is required', 400);
    }

    // Fetch original
    $original = $db->fetchOne(
        "SELECT * FROM qr_codes WHERE id = ?",
        "i",
        [$input['id']]
    );

    if (!$original) {
        jsonResponse(false, null, 'QR code not found', 404);
    }

    // Generate new code
    $newCode = generateUniqueCode(QR_CODE_LENGTH);

    // Insert duplicate
    $newId = $db->insert(
        "INSERT INTO qr_codes (code, title, description, destination_url, tags)
         VALUES (?, ?, ?, ?, ?)",
        "sssss",
        [
            $newCode,
            $original['title'] . ' (Copy)',
            $original['description'],
            $original['destination_url'],
            $original['tags']
        ]
    );

    jsonResponse(true, ['id' => $newId, 'code' => $newCode], 'Duplicated successfully');
}
```

3. **Add JavaScript call:**
```javascript
async function duplicateQrCode(id) {
    const result = await callApi('duplicate', { id });
    if (result.success) {
        location.reload();
    }
}
```

### Task 3: Customize QR Code Defaults

Edit `config.php`:

```php
// Change auto-generated code length (6-10 recommended)
define('QR_CODE_LENGTH', 8);

// Change max custom slug length (1-33)
define('QR_MAX_SLUG_LENGTH', 50);

// Change default QR size
define('QR_DEFAULT_SIZE', 400);
```

### Task 4: Add a Reserved Slug

Edit `config.php`:

```php
define('RESERVED_SLUGS', [
    'admin', 'api', 'create', 'edit', 'index',
    'generated', 'assets', 'includes', 'logs',
    'config', 'database', 'diagnostic', 'save-image',
    'r', 'qr', 'delete', 'update', 'get',
    'new-reserved-slug'  // Add here
]);
```

### Task 5: Change Branding/Colors

Edit `assets/style.css`:

```css
:root {
    /* Update these color variables */
    --primary-color: #YOUR_COLOR;
    --secondary-color: #YOUR_COLOR;
    --accent-color: #YOUR_COLOR;

    /* Update gradient backgrounds */
    --gradient: linear-gradient(135deg, #COLOR1 0%, #COLOR2 100%);
}
```

---

## 🔒 Security Guidelines

### Critical Security Rules

1. **NEVER commit sensitive files:**
   - `config.php` - Contains database credentials
   - `.htpasswd` - Contains password hashes
   - These are in `.gitignore` for a reason

2. **Always use prepared statements:**
   ```php
   // ✅ CORRECT
   $db->fetchOne("SELECT * FROM qr_codes WHERE code = ?", "s", [$code]);

   // ❌ WRONG - SQL Injection vulnerability
   $db->query("SELECT * FROM qr_codes WHERE code = '$code'");
   ```

3. **Always sanitize output:**
   ```php
   // ✅ CORRECT
   echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

   // ❌ WRONG - XSS vulnerability
   echo $userInput;
   ```

4. **Always validate input:**
   ```php
   // Validate URL
   if (!isValidUrl($url)) {
       jsonResponse(false, null, 'Invalid URL', 400);
   }

   // Validate slug
   $validation = isValidSlug($slug);
   if (!$validation['valid']) {
       jsonResponse(false, null, $validation['error'], 400);
   }
   ```

5. **Protect sensitive files in .htaccess:**
   ```apache
   <Files "config.php">
       Order Allow,Deny
       Deny from all
   </Files>
   ```

6. **Use HTTP Basic Auth for admin pages:**
   - Configured in `.htaccess`
   - Password file at `.htpasswd`
   - Never commit `.htpasswd`

### Threat Model

**Potential Attacks:**

| Attack | Protection | Location |
|--------|-----------|----------|
| SQL Injection | Prepared statements | All database queries |
| XSS | `htmlspecialchars()` | All output of user data |
| CSRF | Admin pages behind auth | .htaccess |
| Path Traversal | Input validation | `helpers.php` |
| Code Injection | Input sanitization | All form handlers |
| Direct File Access | .htaccess rules | .htaccess |

**When Adding Features:**
- Ask: "Can a malicious user inject code here?"
- Ask: "Is this input validated and sanitized?"
- Ask: "Could this expose sensitive data?"

---

## 🧪 Testing & Debugging

### Local Testing Environment

**Option 1: PHP Built-in Server (Quick testing)**
```bash
php -S localhost:8000
```
**Note:** .htaccess won't work, test direct page access only.

**Option 2: Local Apache (Full testing)**
```bash
# Ubuntu/Debian
sudo apt install apache2 php libapache2-mod-php php-mysqli
sudo a2enmod rewrite
sudo systemctl restart apache2

# macOS (Homebrew)
brew install php
brew services start php
```

### Testing Checklist

**Database Operations:**
- [ ] Create QR code with auto-generated code
- [ ] Create QR code with custom slug
- [ ] Update QR code destination URL
- [ ] Update QR code slug (with warning)
- [ ] Delete QR code
- [ ] Reset click counter

**Validation:**
- [ ] Try invalid destination URL
- [ ] Try reserved slug
- [ ] Try duplicate slug
- [ ] Try slug with invalid characters
- [ ] Try slug over length limit

**Redirects:**
- [ ] Scan QR code (or visit clean URL)
- [ ] Verify redirect works
- [ ] Verify click counter increments
- [ ] Try non-existent code (404 page)

**Security:**
- [ ] Try accessing admin pages without auth
- [ ] Try accessing config.php directly
- [ ] Try SQL injection in forms
- [ ] Try XSS in title/description

**Responsive Design:**
- [ ] Test on mobile viewport
- [ ] Test on tablet viewport
- [ ] Test on desktop

### Debugging Tools

**Check Error Logs:**
```bash
tail -f logs/error.log
```

**Enable PHP Errors:**
In `config.php` for development:
```php
define('ENABLE_ERROR_LOG', false);  // Shows errors on screen
```

**Database Debugging:**
Add temporary logging in `Database.php`:
```php
public function query($sql, $types = "", $params = []) {
    error_log("SQL: $sql | Params: " . json_encode($params));
    // ... rest of method
}
```

**JavaScript Console:**
```javascript
console.log('Debug data:', data);
console.table(arrayData);
```

---

## 🌿 Git Workflow

### Branch Naming Convention

**Format:** `claude/<feature-description>-<session-id>`

**Examples:**
- `claude/custom-slugs-01W34A7iAchLTSjqqUh1yrda`
- `claude/design-refactor-01W34A7iAchLTSjqqUh1yrda`

### Commit Message Format

**Pattern:** `<Type>: <Description>`

**Types:**
- `Feature:` - New functionality
- `Fix:` - Bug fixes
- `Docs:` - Documentation changes
- `Style:` - CSS/UI changes
- `Refactor:` - Code restructuring
- `Perf:` - Performance improvements
- `Test:` - Testing additions
- `Chore:` - Maintenance tasks

**Examples:**
```
Feature: Add custom URL slug support
Fix: Update .htaccess to support hyphens in slugs
Docs: Update README with custom slug instructions
Design: Refactor to Nest Hostels brand design system
```

### Pull Request Process

1. **Create branch:**
   ```bash
   git checkout -b claude/feature-name-sessionid
   ```

2. **Make changes and commit:**
   ```bash
   git add .
   git commit -m "Feature: Add new capability"
   ```

3. **Push to remote:**
   ```bash
   git push -u origin claude/feature-name-sessionid
   ```

4. **Create Pull Request:**
   - Use GitHub web interface or `gh` CLI
   - Title: Clear description of changes
   - Body: Detailed explanation with:
     - What changed
     - Why it changed
     - How to test
     - Any breaking changes

5. **Merge Strategy:**
   - PRs are merged to main branch
   - Use squash merge for clean history

### What NOT to Commit

**Never commit these files** (they're in `.gitignore`):
- `config.php` - Database credentials
- `.htpasswd` - Password hashes
- `/generated/*.png|svg|jpg` - Generated QR images
- `/logs/*.log` - Error logs
- IDE files (`.vscode/`, `.idea/`)

**Always commit:**
- `config.example.php` - Template for config
- `database.sql` - Schema
- All source code
- Documentation
- `.gitkeep` files in empty directories

---

## 🚀 Deployment

### Deployment Checklist

**Pre-deployment:**
- [ ] Test all features locally
- [ ] Run database queries without errors
- [ ] Verify .htaccess syntax
- [ ] Check file permissions
- [ ] Update README.md if needed

**FTP Upload:**
- [ ] Upload all files except: `config.php`, `.htpasswd`, `.git/`, `generated/*`, `logs/*`
- [ ] Set directory permissions: `generated/` (755), `logs/` (755)
- [ ] Upload `config.example.php` → Copy to `config.php` on server
- [ ] Edit `config.php` with production credentials

**Database Setup:**
- [ ] Create MySQL database
- [ ] Import `database.sql`
- [ ] Create database user with full privileges
- [ ] Update `config.php` with credentials

**Authentication:**
- [ ] Create `.htpasswd` file:
   ```bash
   htpasswd -c .htpasswd admin
   ```
- [ ] Update `.htaccess` line 50 with absolute path to `.htpasswd`
- [ ] Test login at `/index.php`

**Testing on Production:**
- [ ] Visit admin pages (should prompt for login)
- [ ] Create test QR code
- [ ] Test redirect (scan QR or visit clean URL)
- [ ] Verify click tracking
- [ ] Test 404 page
- [ ] Check error logs

**Security Verification:**
- [ ] Try accessing `config.php` directly (should be blocked)
- [ ] Try accessing `/logs/` (should be blocked)
- [ ] Verify admin pages require authentication
- [ ] Verify r.php is publicly accessible

### Rollback Plan

If deployment fails:

1. **Keep backup of working files** before upload
2. **Database backup** before schema changes:
   ```bash
   mysqldump -u user -p database_name > backup.sql
   ```
3. **Restore files** via FTP
4. **Restore database**:
   ```bash
   mysql -u user -p database_name < backup.sql
   ```

---

## 📚 Additional Resources

### Key Files to Reference

- **User Guide:** `README.md` - Installation and usage instructions
- **Project Brief:** `docs/BRIEF.md` - Original requirements
- **Config Template:** `config.example.php` - All configuration options
- **Database Schema:** `database.sql` - Table structure

### Quick Reference Commands

```bash
# View error logs
tail -f logs/error.log

# Find usage of a function
grep -r "functionName" --include="*.php" .

# Check database connection
php diagnostic.php

# Test PHP syntax
php -l filename.php

# Start local server
php -S localhost:8000

# Git status
git status

# Git log
git log --oneline --decorate -10
```

### Common Pitfalls

1. **Forgetting to set directory permissions**
   - Symptom: "Failed to save image" errors
   - Fix: `chmod 755 generated/ logs/`

2. **Incorrect .htpasswd path**
   - Symptom: Authentication not working
   - Fix: Use absolute path in `.htaccess` line 50

3. **mod_rewrite not enabled**
   - Symptom: Clean URLs return 404
   - Fix: `sudo a2enmod rewrite && sudo systemctl restart apache2`

4. **Database connection errors**
   - Symptom: "Database connection error"
   - Fix: Verify credentials in `config.php`

5. **Committing sensitive files**
   - Symptom: Security vulnerability
   - Fix: Remove from git history, update `.gitignore`

---

## 🎯 Best Practices for AI Assistants

When working on this codebase:

1. **Always read existing code first** before suggesting changes
2. **Maintain consistency** with existing patterns and style
3. **Prioritize security** - validate and sanitize all inputs
4. **Test thoroughly** - provide testing steps with changes
5. **Document changes** - update README.md and this file
6. **Keep it simple** - avoid over-engineering
7. **Respect the stack** - don't introduce unnecessary dependencies
8. **Consider deployment** - remember this deploys via FTP

### When Suggesting Changes

**Good Suggestion:**
```
I'll add a new field to track QR code categories. This requires:
1. ALTER TABLE to add 'category' column
2. Update api.php create/update handlers
3. Add form field in create.php and edit.php
4. Update dashboard display in index.php
5. Add validation in helpers.php

Testing: Create QR with category, verify saves, displays, and filters correctly.
```

**Bad Suggestion:**
```
Let's refactor this to use Laravel framework and React frontend!
```
(❌ Introduces massive dependencies incompatible with deployment method)

---

## 📞 Support

For questions about this codebase:
1. Read this CLAUDE.md file thoroughly
2. Check README.md for user-facing documentation
3. Review docs/BRIEF.md for original requirements
4. Examine existing code for patterns
5. Test changes in local environment first

---

**Last Updated:** 2025-11-21
**Version:** 1.0
**Maintained By:** Claude Code + Artur Mamedov
