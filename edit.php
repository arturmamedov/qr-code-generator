<?php
/**
 * Edit QR Code Page
 *
 * Form for editing existing QR codes
 * Protected by HTTP Basic Auth via .htaccess
 */

require_once __DIR__ . '/includes/init.php';

// Get QR code ID from query parameter
$qrId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($qrId <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch QR code from database
$qrCode = $db->fetchOne(
    "SELECT id, code, title, description, destination_url, click_count, tags,
            created_at, updated_at
     FROM qr_codes
     WHERE id = ?",
    "i",
    [$qrId]
);

// If QR code not found, redirect to dashboard
if (!$qrCode) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Edit QR Code';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - QR Code Manager</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='%23667eea'/><rect x='10' y='10' width='30' height='30' fill='white'/><rect x='60' y='10' width='30' height='30' fill='white'/><rect x='10' y='60' width='30' height='30' fill='white'/></svg>">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div>
                <a href="index.php" class="back-link">← Back to Dashboard</a>
                <h1>Edit QR Code</h1>
            </div>
        </header>

        <!-- Edit Form -->
        <div class="form-layout">
            <!-- Left Column: Form Fields -->
            <div class="form-column">
                <form id="editForm" class="qr-form" data-id="<?php echo $qrCode['id']; ?>" data-code="<?php echo $qrCode['code']; ?>">
                    <!-- Basic Information -->
                    <div class="form-section">
                        <h2>Basic Information</h2>

                        <div class="form-group">
                            <label for="new_slug">URL Slug</label>
                            <div class="slug-input-wrapper">
                                <input type="text"
                                       id="new_slug"
                                       name="new_slug"
                                       value="<?php echo htmlspecialchars($qrCode['code']); ?>"
                                       placeholder="e.g., summer-sale, product-launch"
                                       maxlength="33"
                                       autocomplete="off"
                                       pattern="[a-zA-Z0-9_-]+"
                                       title="Double-click to edit slug"
                                       data-original="<?php echo htmlspecialchars($qrCode['code']); ?>"
                                       data-clicks="<?php echo $qrCode['click_count']; ?>"
                                       class="slug-locked"
                                       readonly>
                                <button type="button" id="autoGenerateBtn" class="btn-auto-generate slug-btn-locked" title="Double-click slug field first to enable" disabled>
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M13.65 2.35C12.2 0.9 10.21 0 8 0C3.58 0 0.01 3.58 0.01 8C0.01 12.42 3.58 16 8 16C11.73 16 14.84 13.45 15.73 10H13.65C12.83 12.33 10.61 14 8 14C4.69 14 2 11.31 2 8C2 4.69 4.69 2 8 2C9.66 2 11.14 2.69 12.22 3.78L9 7H16V0L13.65 2.35Z" fill="currentColor"/>
                                    </svg>
                                    Auto
                                </button>
                            </div>
                            <div id="slugFeedback" class="slug-feedback"></div>
                            <div id="slugSuggestions" class="slug-suggestions" style="display: none;"></div>
                            <?php if ($qrCode['click_count'] > 0): ?>
                            <div class="warning-message" id="slugWarning" style="display: none;">
                                ⚠️ This QR code has <strong><?php echo $qrCode['click_count']; ?> click<?php echo $qrCode['click_count'] != 1 ? 's' : ''; ?></strong>.
                                Changing the slug will break existing QR codes that have been printed or distributed.
                            </div>
                            <?php endif; ?>
                            <small class="help-text">
                                Current URL: <strong><?php echo getQrUrl($qrCode['code']); ?></strong>
                                <span id="slugCharCounter" class="char-counter"><?php echo strlen($qrCode['code']); ?>/33</span>
                                <br>
                                <em>💡 Double-click the slug field to edit</em>
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="title" class="required">Title</label>
                            <input type="text"
                                   id="title"
                                   name="title"
                                   value="<?php echo htmlspecialchars($qrCode['title']); ?>"
                                   placeholder="e.g., Restaurant Menu, Contact Card"
                                   required
                                   maxlength="255">
                            <small class="help-text">A descriptive name for this QR code</small>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description"
                                      name="description"
                                      placeholder="Optional notes about this QR code"
                                      rows="3"><?php echo htmlspecialchars($qrCode['description']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="destination_url" class="required">Destination URL</label>
                            <input type="url"
                                   id="destination_url"
                                   name="destination_url"
                                   value="<?php echo htmlspecialchars($qrCode['destination_url']); ?>"
                                   placeholder="https://example.com"
                                   required>
                            <small class="help-text">Where should this QR code redirect to?</small>
                        </div>

                        <div class="form-group">
                            <label for="tags">Tags</label>
                            <input type="text"
                                   id="tags"
                                   name="tags"
                                   value="<?php echo htmlspecialchars($qrCode['tags']); ?>"
                                   placeholder="restaurant, menu, contact (comma-separated)"
                                   maxlength="255">
                            <small class="help-text">Optional tags for organizing your QR codes</small>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="form-section">
                        <h2>Statistics</h2>

                        <div class="stats-display">
                            <div class="stat-item">
                                <label>Total Clicks</label>
                                <div class="stat-value-large"><?php echo number_format($qrCode['click_count']); ?></div>
                            </div>
                            <div class="stat-item">
                                <label>Created</label>
                                <div class="stat-value-large"><?php echo formatDate($qrCode['created_at'], 'M d, Y'); ?></div>
                            </div>
                            <div class="stat-item">
                                <label>Last Updated</label>
                                <div class="stat-value-large"><?php echo formatDate($qrCode['updated_at'], 'M d, Y'); ?></div>
                            </div>
                        </div>

                        <button type="button" id="resetClicks" class="btn btn-warning" data-id="<?php echo $qrCode['id']; ?>">
                            Reset Click Counter
                        </button>
                    </div>

                    <!-- QR Code Styling -->
                    <div class="form-section">
                        <h2>QR Code Styling</h2>
                        <p class="info-message">💡 You can regenerate the QR code with different styling without changing the short URL.</p>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="qr_size">Size (pixels)</label>
                                <input type="number"
                                       id="qr_size"
                                       name="qr_size"
                                       value="300"
                                       min="200"
                                       max="1000"
                                       step="50">
                            </div>

                            <div class="form-group">
                                <label for="qr_margin">Margin</label>
                                <input type="number"
                                       id="qr_margin"
                                       name="qr_margin"
                                       value="10"
                                       min="0"
                                       max="50"
                                       step="5">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="qr_dots_type">Dot Style</label>
                            <select id="qr_dots_type" name="qr_dots_type">
                                <option value="rounded">Rounded</option>
                                <option value="dots">Dots</option>
                                <option value="classy">Classy</option>
                                <option value="classy-rounded">Classy Rounded</option>
                                <option value="square">Square</option>
                                <option value="extra-rounded">Extra Rounded</option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="qr_dots_color">Dots Color</label>
                                <input type="color"
                                       id="qr_dots_color"
                                       name="qr_dots_color"
                                       value="#000000">
                            </div>

                            <div class="form-group">
                                <label for="qr_bg_color">Background Color</label>
                                <input type="color"
                                       id="qr_bg_color"
                                       name="qr_bg_color"
                                       value="#ffffff">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="qr_corners_type">Corner Square Style</label>
                            <select id="qr_corners_type" name="qr_corners_type">
                                <option value="square">Square</option>
                                <option value="extra-rounded">Extra Rounded</option>
                                <option value="dot">Dot</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="qr_corner_dots_type">Corner Dot Style</label>
                            <select id="qr_corner_dots_type" name="qr_corner_dots_type">
                                <option value="square">Square</option>
                                <option value="dot">Dot</option>
                            </select>
                        </div>
                    </div>

                    <!-- Logo Upload (Optional) -->
                    <div class="form-section">
                        <h2>Logo (Optional)</h2>

                        <div class="form-group">
                            <label for="qr_logo">Upload Logo</label>
                            <input type="file"
                                   id="qr_logo"
                                   name="qr_logo"
                                   accept="image/png, image/jpeg, image/jpg">
                            <small class="help-text">Add a logo in the center of your QR code (PNG, JPG)</small>
                        </div>

                        <div id="logoPreview" class="logo-preview" style="display: none;">
                            <img id="logoPreviewImage" src="" alt="Logo preview">
                            <button type="button" id="removeLogo" class="btn-remove-logo">Remove</button>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="button" id="updatePreview" class="btn btn-secondary">
                            Update Preview
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Update QR Code
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Column: Live Preview -->
            <div class="preview-column">
                <div class="preview-container">
                    <h2>QR Code Preview</h2>

                    <div class="preview-box">
                        <div id="qrPreview" class="qr-preview-canvas"></div>
                    </div>

                    <div class="preview-info">
                        <div class="info-item">
                            <strong>Short URL:</strong>
                            <code id="shortUrl"><?php echo getQrUrl($qrCode['code']); ?></code>
                            <button class="btn-copy" data-url="<?php echo getQrUrl($qrCode['code']); ?>" title="Copy URL">
                                📋
                            </button>
                        </div>
                    </div>

                    <!-- Download Options -->
                    <div class="download-options">
                        <h3>Download QR Code</h3>
                        <div class="download-buttons">
                            <button type="button" id="downloadPng" class="btn btn-download">
                                PNG
                            </button>
                            <button type="button" id="downloadSvg" class="btn btn-download">
                                SVG
                            </button>
                            <button type="button" id="downloadJpg" class="btn btn-download">
                                JPG
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content success-modal">
            <div class="success-icon">✅</div>
            <h3>QR Code Updated Successfully!</h3>
            <p>Your changes have been saved.</p>
            <div class="modal-actions">
                <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
                <button type="button" id="closeSuccess" class="btn btn-primary">Continue Editing</button>
            </div>
        </div>
    </div>

    <!-- Reset Confirmation Modal -->
    <div id="resetModal" class="modal">
        <div class="modal-content">
            <h3>Reset Click Counter</h3>
            <p>Are you sure you want to reset the click counter to zero?</p>
            <p class="warning">Current clicks: <strong><?php echo number_format($qrCode['click_count']); ?></strong></p>
            <div class="modal-actions">
                <button id="cancelReset" class="btn btn-secondary">Cancel</button>
                <button id="confirmReset" class="btn btn-danger">Reset Counter</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <!-- Load QR Code Styling Library -->
    <script src="https://cdn.jsdelivr.net/npm/qr-code-styling@1.6.0-rc.1/lib/qr-code-styling.js"></script>
    <script src="assets/app.js"></script>
</body>
</html>
