# 🎯 QR Code Manager

A self-hosted QR code management system with dynamic redirect capabilities. Create QR codes that point to short URLs which can be changed anytime without regenerating the QR code.

## ✨ Features

- **Dynamic Redirects**: Change destination URLs without regenerating QR codes
- **Click Tracking**: Monitor how many times each QR code is scanned
- **Custom Styling**: Customize QR code appearance (colors, dot styles, corners)
- **Logo Support**: Add your logo to the center of QR codes
- **Multiple Formats**: Download QR codes as PNG, SVG, or JPG
- **Clean URLs**: User-friendly short URLs (e.g., `qr.nestshostels.com/ABC123`)
- **Admin Dashboard**: Manage all QR codes from a single interface
- **Secure**: Protected admin area with HTTP Basic Authentication
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile
- **Error Logging**: Built-in error logging for debugging

## 📋 Requirements

- **Web Server**: Apache with mod_rewrite enabled
- **PHP**: Version 8.0 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.2+
- **Extensions**: MySQLi, GD (for image handling)
- **Browser**: Modern browser with JavaScript enabled (for admin panel)

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
3. Regenerate with new styling if desired
4. Click "Update QR Code"

**Note**: The short code (e.g., ABC123) never changes! All existing QR codes will automatically redirect to the new URL.

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

### Clean URLs not working (/ABC123)

- Ensure mod_rewrite is enabled: `a2enmod rewrite` (Linux)
- Check `.htaccess` RewriteBase setting
- Verify Apache AllowOverride is set to "All"

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
├── edit.php              # QR edit form
├── api.php               # API endpoint (CRUD operations)
├── r.php                 # Public redirect handler
├── save-image.php        # Image upload handler
├── config.php            # Configuration (DO NOT COMMIT)
├── config.example.php    # Configuration template
├── database.sql          # Database schema
├── .htaccess            # Apache configuration
├── .htpasswd            # Authentication file (DO NOT COMMIT)
├── /includes/
│   ├── Database.php      # Database class
│   ├── helpers.php       # Helper functions
│   └── init.php          # Initialization
├── /assets/
│   ├── style.css         # Stylesheet
│   └── app.js           # JavaScript
├── /generated/           # QR code images (writable)
├── /logs/               # Error logs (writable)
└── /docs/               # Documentation
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
define('QR_CODE_LENGTH', 6);  // Length of short codes
define('QR_DEFAULT_SIZE', 300); // Default QR size
```

### Error Logging

Enable/disable in `config.php`:

```php
define('ENABLE_ERROR_LOG', true);
```

## 🚀 Future Enhancements

Ideas for future versions (currently not implemented):

- ✅ Search and filter QR codes
- ✅ Column sorting in dashboard
- ✅ Pagination for large datasets
- ✅ Bulk operations (delete, export)
- ✅ CSV export for analytics
- ✅ Date range filtering
- ✅ QR code categories/folders
- ✅ API key authentication for external integrations
- ✅ Statistics dashboard with charts
- ✅ Scheduled URL changes
- ✅ A/B testing for destinations
- ✅ Geolocation tracking (with privacy considerations)

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
