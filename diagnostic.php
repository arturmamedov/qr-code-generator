<?php
/**
 * Diagnostic Script
 * Run this to check your server configuration
 * DELETE THIS FILE after testing!
 */

echo "<h1>QR Code Manager - Diagnostic</h1>";
echo "<pre>";

// PHP Version
echo "✓ PHP Version: " . PHP_VERSION . "\n";

// Required Extensions
echo "\n=== PHP Extensions ===\n";
$required = ['mysqli', 'gd', 'json'];
foreach ($required as $ext) {
    $status = extension_loaded($ext) ? "✓ INSTALLED" : "✗ MISSING";
    echo "$status: $ext\n";
}

// File Permissions
echo "\n=== File Permissions ===\n";
$dirs = ['generated', 'logs', 'includes', 'assets'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir) ? "✓ WRITABLE" : "✗ NOT WRITABLE";
        echo "$writable: $dir ($perms)\n";
    } else {
        echo "✗ MISSING: $dir\n";
    }
}

// Config file
echo "\n=== Configuration ===\n";
if (file_exists('config.php')) {
    echo "✓ config.php exists\n";
} else {
    echo "✗ config.php MISSING - Copy config.example.php to config.php\n";
}

if (file_exists('.htpasswd')) {
    echo "✓ .htpasswd exists\n";
} else {
    echo "✗ .htpasswd MISSING - Create for admin authentication\n";
}

// .htaccess
echo "\n=== Apache Configuration ===\n";
if (file_exists('.htaccess')) {
    echo "✓ .htaccess exists\n";
    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        echo (in_array('mod_rewrite', $modules) ? "✓" : "✗") . " mod_rewrite\n";
    } else {
        echo "? mod_rewrite (cannot detect)\n";
    }
} else {
    echo "✗ .htaccess MISSING\n";
}

// Directory path
echo "\n=== Paths ===\n";
echo "Current directory: " . __DIR__ . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";

echo "</pre>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Create config.php from config.example.php</li>";
echo "<li>Create .htpasswd file for authentication</li>";
echo "<li>Ensure generated/ and logs/ are writable (755)</li>";
echo "<li>Update .htaccess with correct AuthUserFile path</li>";
echo "<li>DELETE this diagnostic.php file!</li>";
echo "</ol>";
?>
