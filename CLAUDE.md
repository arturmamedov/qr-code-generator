# CLAUDE.md - AI Assistant Guide for QR Code Manager

This document provides comprehensive guidance for AI assistants (like Claude) working on the QR Code Manager codebase.

## 📋 Table of Contents

1. [Development Philosophy](#development-philosophy)
2. [Project Overview](#project-overview)
3. [Project Context & Constraints](#project-context--constraints)
4. [What NOT to Suggest](#what-not-to-suggest)
5. [Architecture](#architecture)
6. [Technology Stack](#technology-stack)
7. [File Structure](#file-structure)
8. [Database Schema](#database-schema)
9. [Key Components](#key-components)
10. [Development Workflows](#development-workflows)
11. [When to Add Abstraction](#when-to-add-abstraction)
12. [Coding Conventions](#coding-conventions)
13. [Common Tasks](#common-tasks)
14. [Security Guidelines](#security-guidelines)
15. [Testing & Debugging](#testing--debugging)
16. [Git Workflow](#git-workflow)
17. [Deployment](#deployment)
18. [Communication Style](#communication-style)
19. [Success Metrics for Suggestions](#success-metrics-for-suggestions)
20. [Best Practices for AI Assistants](#best-practices-for-ai-assistants)

---

## 💡 Development Philosophy

### Simplicity is Intentional, Not a Limitation

This project's straightforward architecture is **BY DESIGN**, not a lack of sophistication. The codebase prioritizes:

- **Readability over cleverness** - Code that any developer can understand and maintain
- **Directness over abstraction** - Solving problems with clear, simple solutions
- **Pragmatism over patterns** - Using what works, not what's "proper"
- **Maintainability over modernity** - Ensuring freelancers and agencies can work on it

### Core Values

**"If it works and it's readable, it's good code"**

This project values:
- ✅ Working solutions over theoretical perfection
- ✅ Simple functions over complex class hierarchies
- ✅ Direct code over layers of abstraction
- ✅ Measured improvements over revolutionary changes
- ✅ Team maintainability over individual cleverness

**DRY and SOLID principles are respected, but pragmatism wins**

Apply principles when they add clear value, not dogmatically:
- Extract functions when duplicated 3+ times, not at 2
- Add classes when they genuinely simplify, not "because OOP"
- Create abstractions when they prevent real problems, not theoretical ones

### Incremental Improvement Approach

**Suggest small, targeted improvements** - not rewrites:
- Enhance existing patterns instead of replacing them
- "Step by step" improvements for safer deployments
- Fix actual problems, not code that "could be better"
- Maintain backward compatibility when possible

### The "Good Enough" Principle

This project embraces "good enough" solutions:
- A simple `if/else` beats a Strategy Pattern for 2 cases
- A procedural function beats a Repository class for basic CRUD
- Direct array access beats a Translation Manager for simple lookups
- MySQL prepared statements beat an ORM for straightforward queries

**Perfect is the enemy of shipped.** Working, maintainable code ships. Over-engineered code doesn't.

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

## 🌍 Project Context & Constraints

### Why This Architecture Exists

The technical choices in this project are **driven by real-world constraints**, not lack of knowledge:

**Deployment Method: FTP to Shared Hosting**
- ❌ No SSH access
- ❌ No composer or package managers
- ❌ No command-line access
- ❌ No build tools (webpack, vite, etc.)
- ✅ Just drag-and-drop files via FTP

**Hosting Environment: Basic Shared Hosting**
- ✅ Apache + PHP + MySQL (that's it)
- ❌ No Node.js runtime
- ❌ No Redis or advanced caching
- ❌ No process managers (PM2, supervisor)
- ❌ No custom server configuration beyond .htaccess

**Team: Maintainable by Anyone**
- Must be editable by freelancers and agencies
- No framework-specific knowledge required
- Can't assume familiarity with modern tooling
- Should be understandable with basic PHP knowledge

**Scale: Small to Medium Usage**
- Hundreds to low thousands of QR codes
- Not millions of requests per second
- Not a high-traffic SaaS application
- Performance is good enough (not over-optimized)

**Budget: Self-Hosted Solution**
- No cloud services (AWS, GCP, Azure)
- No third-party APIs (unless free)
- No monthly SaaS subscriptions
- One-time setup, low ongoing costs

### These Constraints INFORM the Architecture

**That's why:**
- We use plain PHP files → No build step required
- We use mysqli → No composer dependencies
- We use .htaccess → Works on all shared hosting
- We use procedural code → Easy for anyone to modify
- We use vanilla JS → No npm, webpack, or bundlers
- We use simple functions → Quick to understand and debug

**This isn't "legacy" code** - it's **constraint-aware** code.

---

## 🚫 What NOT to Suggest

### Avoid Over-Engineering

**Don't suggest these unless there's a SPECIFIC, MEASURABLE problem:**

❌ **"Let's use Laravel/Symfony framework"**
- Adds massive complexity
- Requires composer (not available via FTP)
- Over-engineered for simple CRUD

❌ **"Let's implement Repository Pattern"**
```php
// DON'T suggest this
class QrCodeRepository {
    public function findByCode($code) { }
    public function save($qrCode) { }
}

// Current approach is fine
function getQrCode($code) {
    return $db->fetchOne("SELECT * FROM qr_codes WHERE code = ?", "s", [$code]);
}
```

❌ **"Let's add an ORM (Eloquent, Doctrine)"**
- mysqli prepared statements are secure and sufficient
- ORMs add complexity without clear benefit here
- More to learn, more to maintain

❌ **"Let's use Dependency Injection Container"**
- Singleton Database class works perfectly
- DI adds abstraction layers for no gain
- Makes code harder to follow

❌ **"Let's convert this to classes and interfaces"**
```php
// DON'T suggest this
interface QrCodeServiceInterface {
    public function generate(QrCodeDTO $dto): QrCodeEntity;
}

class QrCodeService implements QrCodeServiceInterface { }

// Current approach is fine
function generateUniqueCode($length = 6, $maxAttempts = 10, $customSlug = null) {
    // Simple, direct, works
}
```

❌ **"Let's add a frontend framework (React, Vue, Svelte)"**
- Requires build process
- Requires npm (not available)
- Vanilla JS works great for this use case

❌ **"Let's use a CSS framework (Bootstrap, Tailwind)"**
- Custom CSS is already written and works
- No build step needed
- Easier to customize

❌ **"Let's implement caching before measuring performance"**
- Premature optimization
- Add caching when there's a proven bottleneck
- Current performance is sufficient

### Don't Fix What Isn't Broken

❌ **"This could be refactored to be more modern"**
- Modern ≠ Better
- If it works, is readable, and maintainable → it's good

❌ **"Let's split helpers.php into multiple files"**
- Only if it grows beyond 500-1000 lines
- Current organization is fine

❌ **"Let's use static classes instead of functions"**
- Functions are simpler and work perfectly
- Static classes add no value here

### Anti-Patterns to Avoid Suggesting

```php
// ❌ DON'T suggest complex abstractions
class TranslationManagerFactoryWithCaching {
    private $strategyPattern;
    private $cacheLayer;
    // 200 lines of complexity
}

// ✅ Simple and direct is better
function getTranslation($key, $lang = null) {
    // Straightforward implementation
}
```

```php
// ❌ DON'T suggest service layers for simple CRUD
class QrCodeService {
    public function __construct(
        private QrCodeRepository $repo,
        private ValidationService $validator,
        private CacheService $cache
    ) {}
}

// ✅ Direct API handlers work fine
function handleCreate($input) {
    global $db;
    // Validate, sanitize, insert
}
```

---

## 🏗️ Architecture

### Design Philosophy

This project follows a **pragmatic, traditional PHP architecture** that prioritizes simplicity and maintainability:

**No frameworks** - Pure PHP with procedural and minimal OOP
- **Why?** Frameworks add complexity, dependencies, and learning curves
- **Benefit:** Anyone with basic PHP knowledge can contribute
- **Trade-off:** Less "elegant" but more maintainable

**Server-side rendering** - PHP generates complete HTML pages
- **Why?** No build step, no hydration issues, works everywhere
- **Benefit:** Simple to debug, fast initial page loads
- **Trade-off:** Less interactive than SPAs (but we don't need that)

**Vanilla JavaScript** - No frontend framework, minimal JS for interactivity
- **Why?** Modern browsers are powerful enough without frameworks
- **Benefit:** No npm, no build tools, no version conflicts
- **Trade-off:** More verbose JS (but more readable too)

**Single-file pages** - Each page is complete and self-contained
- **Why?** Easy to find code, easy to understand flow
- **Benefit:** No hunting through 10 files to understand one page
- **Trade-off:** Some repetition (but explicit beats implicit)

**Shared utilities in /includes/** - Common code in one place
- **Why?** DRY principle where it adds value
- **Benefit:** Consistent database access, validation, helpers
- **Trade-off:** None - this is good abstraction

### Request Flow

**1. Admin Pages** (index.php, create.php, edit.php):
```
User → .htaccess (HTTP Basic Auth) → PHP page → HTML response
```
- Simple, secure, works on any Apache server
- No sessions to manage, no token refresh, no JWT complexity

**2. API Requests** (api.php):
```
JavaScript → POST to api.php → JSON response → JavaScript updates UI
```
- RESTful-ish API (pragmatic, not dogmatic)
- JSON in, JSON out
- Protected by same HTTP Basic Auth

**3. Public Redirects** (r.php):
```
User scans QR → Clean URL → .htaccess rewrite → r.php → DB lookup → 301 redirect
```
- Fast: Single query, single redirect
- Tracked: Click counter incremented
- Bulletproof: Works even if JavaScript disabled

### One Database Connection Per Request

The `Database` class is a Singleton - one connection, reused throughout the request:

```php
$db = Database::getInstance();
```

**Why Singleton here?**
- ✅ Prevents accidental multiple connections
- ✅ Simple to use: `$db->fetchOne()` anywhere
- ✅ No dependency injection complexity
- ✅ No connection pooling needed (PHP is stateless)

**Why NOT Singleton everywhere?**
- This is the ONLY singleton in the codebase
- Used here because it genuinely simplifies
- Not used as a pattern to follow blindly

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

## ⚖️ When to Add Abstraction

### Guidelines for Adding Complexity

Abstraction and complexity should solve **actual problems**, not theoretical ones. Use these specific criteria:

### ✅ ADD Abstraction When:

**1. Code duplicated 3+ times**
```php
// ✅ Worth extracting
function validateAndSanitizeInput($input) {
    // Used in 5+ places
}
```

**2. Clear performance benefit (measured, not assumed)**
```php
// ✅ Worth caching if proven slow
function getCachedQrCodes() {
    // But measure first!
}
```

**3. Significantly improves security**
```php
// ✅ Worth abstracting for consistency
function sanitizeOutput($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
```

**4. Makes debugging easier (not harder)**
```php
// ✅ Centralized error logging
function logError($message, $level = 'ERROR') {
    // Better than echo/var_dump everywhere
}
```

**5. Prevents real bugs we've actually encountered**
```php
// ✅ After finding double-slash bugs
function getQrUrl($code) {
    return rtrim(BASE_URL, '/') . '/' . $code;
}
```

### ❌ DON'T Add Abstraction When:

**1. "It's more proper OOP"**
```php
// ❌ Don't do this
class QrCodeValueObject {
    private string $code;
    public function getCode(): string { return $this->code; }
}

// ✅ This is fine
$code = $qr['code'];
```

**2. "It follows SOLID principles better"**
```php
// ❌ Unnecessary interface
interface QrCodeFinderInterface {
    public function findByCode(string $code): ?QrCode;
}

// ✅ Simple function works
function getQrCode($code) { }
```

**3. "It's more testable" (when not actually testing)**
```php
// ❌ Don't add DI just for testability if not testing
class QrCodeService {
    public function __construct(
        private DatabaseInterface $db,
        private ValidatorInterface $validator
    ) {}
}

// ✅ Direct usage when not unit testing
function handleCreate($input) {
    global $db;
    // Simple and works
}
```

**4. "It's more scalable" (when scale isn't an issue)**
```php
// ❌ Don't pre-optimize
class CachedQrCodeRepositoryWithRedis { }

// ✅ Start simple, optimize when needed
function getQrCode($code) {
    return $db->fetchOne("SELECT ...");
}
```

**5. "Future requirements might need it"**
- YAGNI: You Aren't Gonna Need It
- Add complexity when requirements are real, not imagined
- Refactoring later is fine (and easier with simple code)

### The "Rule of Three"

**Don't abstract until:**
1. First time: Write it inline
2. Second time: Copy-paste (yes, really)
3. Third time: Extract to function

**Why?**
- First two times, you're learning the pattern
- By the third time, you know what to abstract
- Premature abstraction is harder to fix than duplication

### Acceptable Complexity

Some complexity is worth it:

✅ **Input validation** - Worth centralizing for security
✅ **Error logging** - Better than scattered error handling
✅ **Database wrapper** - Ensures prepared statements everywhere
✅ **URL building** - Prevents double-slash bugs
✅ **Sanitization helpers** - Consistent XSS protection

### Unacceptable Complexity

These add little value in this project:

❌ **Service layers** - Overkill for simple CRUD
❌ **Repository pattern** - mysqli works fine
❌ **Dependency injection** - Globals are okay in PHP
❌ **DTOs/Value Objects** - Arrays work perfectly
❌ **Event systems** - No complex workflows to manage

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

## 💬 Communication Style

### Be Direct and Honest

**Preferred approach:**
- Say "This adds complexity" not "This could be considered slightly more complex"
- Acknowledge trade-offs honestly: "This is simpler but less flexible"
- Direct feedback over diplomatic hedging
- "This won't work because..." beats "You might want to consider..."

### Focus on Specific, Measurable Benefits

**Good:**
- "This reduces database queries from 10 to 1"
- "This fixes the XSS vulnerability on line 42"
- "This makes the code 50% shorter without losing clarity"

**Bad:**
- "This is more maintainable" (vague)
- "This follows best practices" (which practices?)
- "This is more modern" (so what?)

### Acknowledge What's Working

**Before suggesting changes:**
- Point out what's already good
- Explain why the current approach makes sense
- Then explain specific improvement

**Example:**
```
The current slug validation works well and is secure. However,
we could add real-time availability checking to improve UX
by preventing form submission errors. This adds 1 AJAX call
but reduces user frustration.
```

### Structure Suggestions Clearly

```
## Problem: [Specific issue]
Current: [What happens now]
Impact: [Why it matters]

## Solution: [Minimal change]
[Code example that integrates with existing approach]

## Why This Works:
- Solves X without changing Y
- Adds N lines of code
- Improves [specific metric]

## Trade-offs:
- Adds complexity: [yes/no, how much]
- Requires testing: [what to test]
```

### Be Honest About Limits of Knowledge

**Do:**
- "I'm not certain about Apache config on Windows servers"
- "This might not work with PHP 7.4, check the docs"
- "I haven't tested this with mysqli, verify first"

**Don't:**
- Pretend certainty when unsure
- Suggest changes without considering edge cases
- Ignore potential breaking changes

---

## ✅ Success Metrics for Suggestions

### Good Suggestions Should:

✅ **Solve a specific, identifiable problem**
- Not: "This could be better"
- Yes: "This prevents the XSS vulnerability found in testing"

✅ **Integrate easily with existing code**
- Works with current patterns
- Doesn't require refactoring other files
- Maintains consistency

✅ **Be understandable by other developers**
- Junior dev can read and understand
- No "magic" that requires framework knowledge
- Clear comments for complex parts

✅ **Provide measurable improvements**
- Faster: "Reduces load time by X ms"
- Safer: "Prevents SQL injection in form handler"
- Clearer: "Reduces function from 50 to 20 lines"

✅ **Maintain or improve site performance**
- Doesn't add significant overhead
- Doesn't require new dependencies
- Works within hosting constraints

### Avoid Suggestions That:

❌ **Require massive refactoring**
- "Let's restructure the entire project"
- "Convert all functions to classes"
- "Migrate to a different tech stack"

❌ **Add unnecessary complexity**
- Design patterns for their own sake
- Abstractions that don't solve real problems
- "Future-proofing" for unlikely scenarios

❌ **Break existing functionality**
- Changes that aren't backward compatible
- Modifications without considering dependencies
- Risky changes without clear benefits

❌ **Introduce new dependencies without clear benefits**
- "Let's add this library" (when native PHP works)
- "Install this package" (when we can't use composer)
- "Use this framework" (adds megabytes for kilobytes of benefit)

❌ **Change working code just to be "more modern"**
- "This should use arrow functions" (when regular functions are fine)
- "Convert to classes" (when functions work perfectly)
- "Use latest PHP features" (when current version is fine)

### Red Flags in Suggestions

Watch out for these phrases in your own suggestions:

🚩 "This is more proper..."
🚩 "This follows best practices..."
🚩 "This is more scalable..." (without scale issues)
🚩 "This is more testable..." (when not testing)
🚩 "We should use X pattern..." (without explaining benefit)
🚩 "Future requirements might need..." (YAGNI violation)

### Green Flags in Suggestions

Look for these characteristics:

✅ Solves actual bug or security issue
✅ Improves performance (measured)
✅ Makes code clearer (demonstrably)
✅ Fixes accessibility problem
✅ Enhances user experience
✅ Reduces maintenance burden

---

## 🎯 Best Practices for AI Assistants

### Respect the Intentional Simplicity

**This codebase is simple BY CHOICE, not by accident.**

- Don't suggest "modernizing" working code
- Don't propose frameworks or major architectural changes
- Don't fix code that isn't broken
- Don't add abstraction "because it's better"

### Understand Before Changing

**Before suggesting any change:**

1. **Read the existing code** - Don't skim, actually read it
2. **Understand WHY it's written that way** - There's usually a good reason
3. **Check for dependencies** - `grep -r "functionName"` to see usage
4. **Consider the constraints** - Remember: FTP deployment, no composer, basic hosting
5. **Ask: Is this solving a real problem?** - Not a theoretical one

### Focus on Actual Problems

**Suggest changes ONLY when there's:**

✅ **A security vulnerability** - XSS, SQL injection, exposed files
✅ **A performance bottleneck** - MEASURED, not assumed
✅ **A bug** - Something doesn't work as intended
✅ **An accessibility issue** - Screen readers, keyboard nav, contrast
✅ **A user experience problem** - Confusing UI, unclear errors
✅ **Missing error handling** - Silent failures, unclear error messages

**DON'T suggest changes for:**

❌ "Code quality" without specific issues
❌ "Best practices" that don't apply here
❌ "Maintainability" that adds complexity
❌ "Scalability" when scale isn't an issue
❌ "Modern patterns" without clear benefits

### Provide Specific, Actionable Suggestions

**Good example:**
```
## Problem: XSS vulnerability in QR code title display
Line 156 in index.php outputs user input without sanitization.

## Fix:
echo htmlspecialchars($qr['title'], ENT_QUOTES, 'UTF-8');

## Impact:
- Prevents XSS attacks
- 1 line change
- No dependencies
- Backward compatible
```

**Bad example:**
```
The codebase could benefit from implementing a Model-View-Controller
architecture with dependency injection and repository patterns for
better code organization and maintainability.
```

### Remember the Constraints

**Every suggestion must work with:**
- ✅ FTP deployment (no build step)
- ✅ Shared hosting (no special services)
- ✅ No composer (no package manager)
- ✅ Basic Apache + PHP + MySQL
- ✅ Maintainable by junior developers

**If your suggestion requires:**
- ❌ Command-line access → Won't work
- ❌ npm or build tools → Won't work
- ❌ Composer packages → Won't work
- ❌ Advanced PHP extensions → Probably won't work
- ❌ Framework knowledge → Defeats the purpose

### Acknowledge Trade-offs Honestly

**Every change has trade-offs. Be explicit:**

```
✅ Good:
"Adding client-side validation improves UX but adds 50 lines of JS.
The trade-off is worth it because users get immediate feedback."

❌ Bad:
"We should add validation."
```

### Examples of Good vs Bad Suggestions

**Good Suggestions:**

✅ "Add index on `click_count` column for faster dashboard sorting"
- Specific problem: Slow sorting on dashboard
- Specific solution: Database index
- Measurable benefit: Faster queries
- No complexity added

✅ "Sanitize output in edit.php line 89 to prevent XSS"
- Specific problem: Security vulnerability
- Specific solution: `htmlspecialchars()`
- Clear benefit: Security fix
- Simple change

✅ "Add error message when slug is too long"
- Specific problem: Silent failure confuses users
- Specific solution: Show error message
- User experience improvement
- 3 lines of code

**Bad Suggestions:**

❌ "Implement Repository Pattern for data access"
- No specific problem identified
- Adds significant complexity
- No measurable benefit
- Harder to maintain

❌ "Refactor to use PSR-4 autoloading"
- Requires composer (not available)
- Doesn't solve a problem
- Adds build complexity
- Breaks deployment method

❌ "Convert helper functions to static class methods"
- Stylistic preference, not improvement
- Makes code more verbose
- No actual benefit
- Change for change's sake

### Testing Your Own Suggestions

**Before suggesting, ask yourself:**

1. **Does this solve a specific problem?** (Not "might be better")
2. **Can I measure the improvement?** (Faster, safer, clearer)
3. **Will this work with FTP deployment?** (No build step)
4. **Is it simpler than the current approach?** (Or equally simple)
5. **Would a junior dev understand it?** (Maintainability)

**If you answer "no" to any** → Reconsider the suggestion

### Working With This Developer

**This developer values:**
- ✅ Honest feedback over diplomacy
- ✅ Practical solutions over elegant architecture
- ✅ Working code over perfect code
- ✅ Incremental improvements over rewrites
- ✅ Simplicity over sophistication

**Avoid:**
- ❌ Suggesting changes "because OOP"
- ❌ Proposing frameworks or major refactors
- ❌ Being overly cautious or diplomatic
- ❌ Theoretical improvements without real benefits

**Preferred style:**
- Direct: "This won't work because X"
- Specific: "Change line 42 to fix Y"
- Pragmatic: "This is good enough, ship it"
- Honest: "I'm not sure about Z, test it first"

---

## 📞 Support

For questions about this codebase:
1. Read this CLAUDE.md file thoroughly
2. Check README.md for user-facing documentation
3. Review docs/BRIEF.md for original requirements
4. Examine existing code for patterns
5. Test changes in local environment first

---

**Last Updated:** 2025-11-22
**Version:** 2.0
**Maintained By:** Claude Code + Artur Mamedov

**v2.0 Changes:**
- Added Development Philosophy emphasizing intentional simplicity
- Added Project Context & Constraints explaining WHY the architecture exists
- Added "What NOT to Suggest" section with anti-patterns
- Added "When to Add Abstraction" guidelines with Rule of Three
- Added Communication Style section for direct, honest feedback
- Added Success Metrics for Suggestions with red/green flags
- Completely rewrote Best Practices for AI Assistants
- Reframed Architecture section with pragmatic tone
- Added concrete Good vs Bad suggestion examples throughout
