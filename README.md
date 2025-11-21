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
- **Secure**: Protected admin area with HTTP Basic Authentication
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile
- **Error Logging**: Built-in error logging for debugging

## 📋 Requirements

- **Web Server**: Apache with mod_rewrite enabled
- **PHP**: Version 8.0 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.2+ (JSON column support required)
- **Extensions**: MySQLi, GD (for image handling)
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

The admin pages are protected with HTTP Basic Authentication. You need to create a `.htpasswd` file.

#### Option A: Using htpasswd Command (Recommended)

```bash
# Create .htpasswd file with first user
htpasswd -c .htpasswd admin

# Add additional users (without -c flag)
htpasswd .htpasswd another_user
```

#### Option B: Online Generator

1. Visit: https://www.web2generators.com/apache-tools/htpasswd-generator
2. Enter your desired username and password
3. Copy the generated line
4. Create a file named `.htpasswd` in your root directory
5. Paste the generated line into this file

#### Option C: Using PHP (Delete After Use!)

Create a temporary file `generate_htpasswd.php`:

```php
<?php
$username = 'admin';
$password = 'your_secure_password';
$hash = crypt($password, base64_encode($password));
file_put_contents('.htpasswd', "$username:$hash\n");
echo "Created .htpasswd file. DELETE THIS SCRIPT NOW!";
?>
```

Run it once, then **DELETE** the script immediately!

#### Update .htaccess Path

Edit `.htaccess` file and update line 41 with the **absolute path** to your `.htpasswd` file:

```apache
AuthUserFile /var/www/html/qr.nestshostels.com/.htpasswd
```

To find your absolute path, create a temporary PHP file:
```php
<?php echo __DIR__; ?>
```

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

- Verify `.htpasswd` file exists
- Check absolute path in `.htaccess` (line 41)
- Ensure file permissions on `.htpasswd` (644)
- Try regenerating `.htpasswd` file

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
├── edit.php              # QR edit form (with version gallery)
├── api.php               # API endpoint (CRUD operations)
├── api-versions.php      # Versions API endpoint (NEW)
├── r.php                 # Public redirect handler
├── save-image.php        # Image upload handler
├── config.php            # Configuration (DO NOT COMMIT)
├── config.example.php    # Configuration template
├── database.sql          # Database schema (with versions support)
├── .htaccess            # Apache configuration
├── .htpasswd            # Authentication file (DO NOT COMMIT)
├── /includes/
│   ├── Database.php      # Database class
│   ├── helpers.php       # Helper functions
│   ├── version-helpers.php  # Version management functions (NEW)
│   └── init.php          # Initialization
├── /assets/
│   ├── style.css         # Stylesheet (Nest Hostels branding)
│   └── app.js           # JavaScript (with version gallery)
├── /generated/           # QR code images (writable)
│   └── qr-code-{id}/    # Folder per QR code (NEW STRUCTURE)
│       ├── v1.png       # Version 1 image
│       ├── v2.png       # Version 2 image
│       └── logos/       # Logos subfolder
│           └── logo_v1.png
├── /migrations/          # Database migration scripts (NEW)
│   ├── 001-add-qr-versions.sql
│   ├── 002-migrate-existing-qrs.php
│   └── README.md
├── /logs/               # Error logs (writable)
└── /docs/               # Documentation
    ├── BRIEF.md
    └── FEATURE-QR-VERSIONS.md
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
