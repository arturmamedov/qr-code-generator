# 🎯 QR Code Manager

A self-hosted QR code management system with dynamic redirect capabilities. Create QR codes that point to short URLs which can be changed anytime without regenerating the QR code.

## ✨ Features

- **Dynamic Redirects**: Change destination URLs without regenerating QR codes
- **QR Code Versions**: Create multiple styled versions of the same QR code with different colors, logos, and designs
- **Custom URL Slugs**: Create memorable, branded URLs (e.g., `qr.nestshostels.com/summer-sale`) or auto-generate random codes
- **Favorite Version System**: Mark your preferred version to display in dashboard and manage multiple designs
- **Click Tracking**: Monitor how many times each QR code is scanned (tracked across all versions)
- **Custom Styling**: Customize QR code appearance (colors, dot styles, corners, logos)
- **Logo Support**: Add different logos to different versions of the same QR code
- **Multiple Formats**: Download QR codes as PNG, SVG, or JPG
- **Version Gallery**: View and manage all versions of a QR code in one place
- **Clean URLs**: User-friendly short URLs with support for hyphens and underscores
- **Admin Dashboard**: Manage all QR codes from a single interface with version counts
- **Search & Filter**: Real-time search across titles, codes, destinations, and tags
- **Column Sorting**: Sort QR codes by title, code, clicks, or creation date
- **Pagination**: Navigate large datasets with smart pagination controls
- **Flexible Authentication**: Supabase Auth (email/password, Google OAuth, magic links) or HTTP Basic Auth
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile
- **Environment Configuration**: Supports `.env` files, system env vars (Coolify/Docker), and `config.php`
- **Database Flexibility**: MySQL/MariaDB or SQLite via PDO
- **Error Logging**: Built-in error logging for debugging

## 📋 Requirements

- **Web Server**: Apache with mod_rewrite enabled
- **PHP**: Version 8.0 or higher
- **Database**: MySQL 5.7+ / MariaDB 10.2+ or SQLite 3
- **Extensions**: PDO (pdo_mysql or pdo_sqlite), GD (for image handling)
- **Browser**: Modern browser with JavaScript enabled (for admin panel)
- **Disk Space**: Adequate space for QR code images (each version ~5-50KB depending on styling)

## 🚀 Installation

### Step 1: Upload Files

Upload all files to your web server root directory via FTP:

```
/
├── index.php
├── create.php
├── edit.php
├── api.php
├── r.php
├── save-image.php
├── config.example.php
├── .htaccess
├── database.sql
├── /includes/
├── /assets/
├── /generated/
└── /logs/
```

### Step 2: Create Database

1. Log in to your hosting control panel (cPanel, Plesk, etc.)
2. Create a new MySQL database
3. Create a database user with full privileges
4. Import the `database.sql` file into your database

**Via phpMyAdmin:**
- Select your database
- Click "Import" tab
- Choose `database.sql` file
- Click "Go"

**Via MySQL command line:**
```bash
mysql -u username -p database_name < database.sql
```

### Step 3: Configure Application

1. Copy `config.example.php` to `config.php`:
   ```bash
   cp config.example.php config.php
   ```

2. Edit `config.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   define('BASE_URL', 'https://qr.nestshostels.com');
   ```

3. **IMPORTANT**: Never commit `config.php` to version control!

### Step 4: Set Up Authentication

Choose **one** authentication method:

#### Option 1: Supabase Auth (Recommended for Coolify/Docker)

Supabase Auth provides email/password login, Google OAuth, and magic links.

1. Create a free project at [supabase.com](https://supabase.com)
2. Go to **Settings → API** and copy your keys
3. Set environment variables (in `.env`, Coolify dashboard, or `config.php`):
   ```env
   SUPABASE_URL=https://your-project.supabase.co
   SUPABASE_ANON_KEY=eyJ...
   SUPABASE_JWT_SECRET=your-jwt-secret
   SUPABASE_SERVICE_ROLE_KEY=eyJ...
   ```
4. Create your first user in the Supabase dashboard → **Authentication → Users**
5. Make sure the HTTP Basic Auth block in `.htaccess` is **commented out**

For detailed setup, see [`docs/supabase-auth-guide.md`](docs/supabase-auth-guide.md).

#### Option 2: HTTP Basic Auth (Shared Hosting)

Traditional Apache-based authentication using `.htpasswd`.

1. Create `.htpasswd` file:
   ```bash
   htpasswd -c .htpasswd admin
   ```

2. Uncomment the Basic Auth block in `.htaccess`:
   ```apache
   <FilesMatch "^(index|create|edit|api|api-versions|save-image)\.php$">
       AuthType Basic
       AuthName "QR Code Manager - Admin Area"
       AuthUserFile /absolute/path/to/.htpasswd
       Require valid-user
   </FilesMatch>
   ```

3. Update `AuthUserFile` with the **absolute path** to your `.htpasswd`:
   ```php
   <?php echo __DIR__; ?> <!-- temporary file to find your path -->
   ```

4. Leave `SUPABASE_URL` empty in your config

> **Important:** Never commit `.htpasswd` to version control!

### Step 5: Set Directory Permissions

Ensure the following directories are writable:

```bash
chmod 755 generated
chmod 755 logs
```

Or via FTP: Right-click folder → File Permissions → Set to 755

### Step 6: Test Your Installation

1. **Test redirect handler (public):**
   - Visit: `https://qr.nestshostels.com/TEST` (should show 404 page)

2. **Test admin access (protected):**
   - Visit: `https://qr.nestshostels.com/index.php`
   - You should see a login prompt
   - Enter your credentials from `.htpasswd`

3. **Create your first QR code:**
   - Click "Create New QR Code"
   - Fill in the form
   - Click "Generate QR Code"

## 📖 Usage Guide

### Creating a QR Code

1. Go to admin dashboard
2. Click "Create New QR Code"
3. Fill in required fields:
   - **Title**: Descriptive name (e.g., "Restaurant Menu")
   - **Custom URL Slug** (optional):
     - Enter a memorable slug like `summer-sale`, `menu-2025`, `product-launch`
     - Or leave empty and click "Auto" to generate a random code (e.g., `ABC123`)
     - Supports letters, numbers, hyphens, and underscores (up to 33 characters)
     - Real-time validation shows if slug is available
     - Suggestions provided if slug is already taken
   - **Destination URL**: Where the QR code should redirect
   - **Description** (optional): Additional notes
   - **Tags** (optional): Comma-separated tags
4. Customize appearance:
   - Size, colors, dot style, corners
   - Upload logo (optional)
5. Click "Update Preview" to see changes
6. Click "Generate QR Code"
7. Download in your preferred format (PNG, SVG, JPG)

### Editing a QR Code

1. Click the edit (✏️) button on any QR code
2. Update the destination URL or other details
3. **Changing the Slug** (use with caution):
   - Double-click the URL slug field to unlock editing
   - Enter a new slug or click "Auto" to generate a random one
   - If the QR code has clicks, you'll see a warning
   - Confirm the change (this will break existing printed QR codes!)
4. Click "Update QR Code"

**Important Notes:**
- Changing the slug will break all existing QR codes that have been printed or distributed
- The system will warn you if the QR code has clicks before allowing the change
- If you only need to change the destination URL, you don't need to change the slug!
- To create different visual designs, use the "Create New Version" feature instead

### Managing QR Code Versions (NEW!)

Each QR code can have multiple styled versions with different colors, logos, and designs - all sharing the same URL.

**Creating a New Version:**
1. Open the edit page for any QR code
2. Scroll to the "QR Code Versions" section
3. Click "+ Create New Version"
4. Choose:
   - **Default Style**: Start with default black & white design
   - **Clone Current Version**: Copy the favorite version's styling
5. Optionally name the version (e.g., "Blue Print Version")
6. Click "Create Version"
7. Customize styling using the QR Code Styling section
8. The new version is automatically saved

**Setting a Favorite Version:**
- Each QR code must have one favorite version
- The favorite version is shown in the dashboard
- Click "Set Favorite" on any version to make it the favorite
- The gallery highlights the current favorite with a ⭐ badge

**Downloading Versions:**
- Click "Download" on any version in the gallery
- Downloads the specific version as PNG
- Each version can be downloaded independently

**Deleting Versions:**
- Click "Delete" on any version
- Minimum 1 version required (can't delete the last one)
- If deleting the favorite, another version is automatically set as favorite
- Deleted versions are permanently removed

**Version Gallery:**
- Shows up to 5 versions on the edit page
- If more than 5 versions exist, click "View All Versions"
- Each version shows: preview, name, creation date, and actions
- Version count badge appears next to title in dashboard

### Deleting a QR Code

1. Click the delete (🗑️) button
2. Confirm deletion
3. The QR code and its image will be permanently removed

### Resetting Click Counter

1. Open the edit page for a QR code
2. Click "Reset Click Counter"
3. Confirm the action

### Downloading QR Codes

From the dashboard:
- Click the download (⬇️) button to download the QR code image

From create/edit pages:
- Choose format: PNG, SVG, or JPG
- Click the format button to download

## 🔧 Troubleshooting

### "Database connection error"

- Check `config.php` credentials
- Verify database server is running
- Ensure database user has proper privileges

### "404 Not Found" on admin pages

- Check that `.htaccess` file was uploaded
- Verify Apache has mod_rewrite enabled
- Check file permissions (644 for PHP files)

### Clean URLs not working (/ABC123 or /my-custom-slug)

- Ensure mod_rewrite is enabled: `a2enmod rewrite` (Linux)
- Check `.htaccess` RewriteBase setting
- Verify Apache AllowOverride is set to "All"
- Custom slugs with hyphens/underscores require the updated `.htaccess` pattern
- Pattern should be: `^([A-Za-z0-9_-]{1,33})$` (not the old `^([A-Za-z0-9]{6,10})$`)

### "Permission denied" errors

- Check directory permissions:
  - `generated/`: 755
  - `logs/`: 755
- Ensure web server user can write to these directories

### Authentication not working

**HTTP Basic Auth:**
- Verify `.htpasswd` file exists
- Check absolute path in `.htaccess`
- Ensure file permissions on `.htpasswd` (644)
- Make sure the `<FilesMatch>` block is uncommented in `.htaccess`

**Supabase Auth:**
- Verify `SUPABASE_URL`, `SUPABASE_ANON_KEY`, `SUPABASE_JWT_SECRET` are set
- Check that the JWT secret matches the one in Supabase dashboard → Settings → API
- Ensure your app is served over HTTPS (cookies require `Secure` flag)
- Check browser console for errors
- See `docs/supabase-auth-guide.md` for detailed troubleshooting

### QR code images not saving

- Check `generated/` directory permissions (755)
- Verify PHP has write access
- Check error logs in `logs/error.log`

### Viewing error logs

```bash
tail -f logs/error.log
```

Or download via FTP and open in text editor.

## 📁 Project Structure

```
/
├── index.php              # Admin dashboard
├── create.php             # QR creation form
├── edit.php               # QR edit form (with version gallery)
├── api.php                # API endpoint (CRUD operations)
├── api-versions.php       # Versions API endpoint
├── r.php                  # Public redirect handler
├── save-image.php         # Image upload handler
├── config.php             # Configuration (DO NOT COMMIT)
├── config.example.php     # Configuration template (also works as live config via env())
├── .env                   # Environment variables (DO NOT COMMIT)
├── .env.example           # Environment variables template
├── database.sql           # Database schema (MySQL)
├── database-sqlite.sql    # Database schema (SQLite)
├── .htaccess              # Apache configuration
├── .htpasswd              # Authentication file (DO NOT COMMIT, Basic Auth only)
├── /auth/                 # Supabase Auth pages
│   ├── login.php          # Login page (email/password, Google, magic link)
│   ├── callback.php       # OAuth/magic link callback
│   └── logout.php         # Sign out page
├── /includes/
│   ├── init.php           # Application bootstrap
│   ├── Database.php       # Database class (PDO, MySQL + SQLite)
│   ├── helpers.php        # Helper functions
│   ├── version-helpers.php # Version management functions
│   ├── env-loader.php     # Environment variable loader
│   ├── auth.php           # AuthMiddleware (Supabase JWT verification)
│   └── auth-head.php      # <head> partial for Supabase JS injection
├── /assets/
│   ├── style.css          # Stylesheet (Nest Hostels branding)
│   ├── app.js             # JavaScript (with version gallery)
│   └── auth.js            # Supabase client wrapper
├── /generated/            # QR code images (writable)
│   └── qr-code-{id}/     # Folder per QR code
├── /migrations/           # Database migration scripts
│   ├── 001-add-qr-versions.sql
│   ├── 002-migrate-existing-qrs.php
│   └── README.md
├── /logs/                 # Error logs (writable)
└── /docs/                 # Documentation
    ├── BRIEF.md
    ├── FEATURE-QR-VERSIONS.md
    ├── supabase-auth-guide.md     # Supabase Auth setup guide
    └── supabase-auth-plan.md      # Supabase Auth implementation plan
```

## 🔐 Security Notes

1. **Never commit sensitive files:**
   - `config.php` (database credentials)
   - `.htpasswd` (password hashes)
   - These are in `.gitignore`

2. **Use strong passwords:**
   - Admin authentication password
   - Database password

3. **Keep software updated:**
   - PHP version
   - Database version
   - Dependencies

4. **File permissions:**
   - PHP files: 644
   - Directories: 755
   - `.htaccess`: 644
   - `.htpasswd`: 644

5. **Backup regularly:**
   - Database
   - Generated QR images
   - Configuration files

## 🎨 Customization

### Changing Colors

Edit `assets/style.css` CSS variables:

```css
:root {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
    /* ... more colors ... */
}
```

### QR Code Defaults

Edit `config.php`:

```php
define('QR_CODE_LENGTH', 6);     // Length of auto-generated codes
define('QR_MAX_SLUG_LENGTH', 33); // Maximum length for custom slugs
define('QR_DEFAULT_SIZE', 300);   // Default QR size in pixels
```

### Reserved Slugs

System paths that cannot be used as QR code slugs (defined in `config.php`):

```php
define('RESERVED_SLUGS', [
    'admin', 'api', 'create', 'edit', 'index',
    'generated', 'assets', 'includes', 'logs',
    'config', 'database', 'diagnostic', 'save-image',
    'r', 'qr', 'delete', 'update', 'get'
]);
```

Add additional reserved words to this array as needed.

### Error Logging

Enable/disable in `config.php`:

```php
define('ENABLE_ERROR_LOG', true);
```

## 🚀 Implemented Features

Features that have been added since initial release:

- ✅ **Custom URL Slugs** - Create memorable, branded URLs (e.g., `/summer-sale`, `/menu-2025`)
- ✅ **QR Code Versions** - Create multiple styled versions of the same QR code
  - Multiple designs (colors, logos, styles) for one URL
  - Favorite version system
  - Version gallery with up to 5 previews
  - Independent download for each version
  - Automatic version management (create, set favorite, delete)
  - Folder-based storage: `generated/qr-code-{id}/v{n}.png`
- ✅ **Search and Filter** - Real-time search across all QR code fields
- ✅ **Column Sorting** - Sort by any column with visual indicators
- ✅ **Pagination** - Navigate large datasets efficiently
- ✅ **QR Preview Modal** - Quick preview with full details
- ✅ **Top Performers Widget** - See your most-clicked QR codes
- ✅ **Enhanced Copy Feedback** - Visual confirmation when copying URLs
- ✅ **Nest Hostels Branding** - Custom brand colors and typography
- ✅ **Database Abstraction (PDO)** - Support for both MySQL and SQLite
- ✅ **Environment Variables** - `.env` file support, Coolify/Docker compatibility
- ✅ **Supabase Auth** - Email/password, Google OAuth, and magic link authentication
  - Pure PHP JWT verification (zero dependencies)
  - Backward compatible — falls back to .htaccess when Supabase not configured
  - Login page with tabbed UI (password, magic link, Google)
  - Automatic token refresh and cookie sync

## 💡 Future Enhancement Ideas

Potential features for future versions (not yet implemented):

- Bulk operations (delete, export)
- CSV export for analytics
- Date range filtering
- QR code categories/folders
- API key authentication for external integrations
- Statistics dashboard with charts
- Scheduled URL changes
- A/B testing for destinations
- Geolocation tracking (with privacy considerations)

## 📄 Credits

- **QR Code Library**: [qr-code-styling](https://github.com/kozakdenys/qr-code-styling) by kozakdenys
- **Built for**: qr.nestshostels.com
- **Developed by**: Claude Code with Artur Mamedov

## 📝 License

This project is provided as-is for self-hosted use.

## 🤝 Support

For issues or questions:
1. Check the troubleshooting section above
2. Review error logs: `logs/error.log`
3. Verify all installation steps were completed
4. Check file permissions and server configuration

## 🎉 Enjoy!

You now have a fully functional QR code management system. Happy scanning! 📱
