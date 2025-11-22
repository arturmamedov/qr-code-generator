<?php
/**
 * Create QR Code Page
 *
 * Form for creating new QR codes with live preview
 * Protected by HTTP Basic Auth via .htaccess
 */

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Create QR Code';
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
                <h1>Create QR Code</h1>
            </div>
        </header>

        <!-- Create Form -->
        <div class="form-layout">
            <!-- Left Column: Form Fields -->
            <div class="form-column">
                <form id="createForm" class="qr-form">
                    <!-- Basic Information -->
                    <div class="form-section">
                        <h2>Basic Information</h2>

                        <div class="form-group">
                            <label for="title" class="required">Title</label>
                            <input type="text"
                                   id="title"
                                   name="title"
                                   placeholder="e.g., Restaurant Menu, Contact Card"
                                   required
                                   maxlength="255">
                            <small class="help-text">A descriptive name for this QR code</small>
                        </div>

                        <div class="form-group">
                            <label for="custom_slug">Custom URL Slug</label>
                            <div class="slug-input-wrapper">
                                <input type="text"
                                       id="custom_slug"
                                       name="custom_slug"
                                       placeholder="e.g., summer-sale, product-launch"
                                       maxlength="33"
                                       autocomplete="off"
                                       pattern="[a-zA-Z0-9_-]+"
                                       title="Only letters, numbers, hyphens, and underscores allowed">
                                <button type="button" id="autoGenerateBtn" class="btn-auto-generate" title="Auto-generate random code">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M13.65 2.35C12.2 0.9 10.21 0 8 0C3.58 0 0.01 3.58 0.01 8C0.01 12.42 3.58 16 8 16C11.73 16 14.84 13.45 15.73 10H13.65C12.83 12.33 10.61 14 8 14C4.69 14 2 11.31 2 8C2 4.69 4.69 2 8 2C9.66 2 11.14 2.69 12.22 3.78L9 7H16V0L13.65 2.35Z" fill="currentColor"/>
                                    </svg>
                                    Auto
                                </button>
                            </div>
                            <div id="slugFeedback" class="slug-feedback"></div>
                            <div id="slugSuggestions" class="slug-suggestions" style="display: none;"></div>
                            <small class="help-text">
                                Create a custom, memorable URL (e.g., <strong><?php echo BASE_URL; ?>/your-slug</strong>).
                                Leave empty to auto-generate. <span id="slugCharCounter" class="char-counter">0/33</span>
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description"
                                      name="description"
                                      placeholder="Optional notes about this QR code"
                                      rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="destination_url" class="required">Destination URL</label>
                            <input type="url"
                                   id="destination_url"
                                   name="destination_url"
                                   placeholder="https://example.com"
                                   required>
                            <small class="help-text">Where should this QR code redirect to?</small>
                        </div>

                        <div class="form-group">
                            <label for="tags">Tags</label>
                            <input type="text"
                                   id="tags"
                                   name="tags"
                                   placeholder="restaurant, menu, contact (comma-separated)"
                                   maxlength="255">
                            <small class="help-text">Optional tags for organizing your QR codes</small>
                        </div>
                    </div>

                    <!-- QR Code Styling -->
                    <div class="form-section">
                        <h2>QR Code Styling</h2>

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
                            Generate QR Code
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Column: Live Preview -->
            <div class="preview-column">
                <div class="preview-container">
                    <h2>Live Preview</h2>

                    <div class="preview-box">
                        <div id="qrPreview" class="qr-preview-canvas"></div>
                        <div id="previewPlaceholder" class="preview-placeholder">
                            <div class="placeholder-icon">📱</div>
                            <p>Your QR code will appear here</p>
                            <small>Fill in the destination URL to see a preview</small>
                        </div>
                    </div>

                    <div class="preview-info">
                        <div class="info-item">
                            <strong>Short URL:</strong>
                            <code id="shortUrl">-</code>
                        </div>
                    </div>

                    <!-- Download Options (shown after generation) -->
                    <div id="downloadOptions" class="download-options" style="display: none;">
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
            <h3>QR Code Created Successfully!</h3>
            <p>Your QR code has been generated and saved.</p>
            <div class="success-info">
                <div class="info-item">
                    <strong>Short Code:</strong>
                    <code id="successCode"></code>
                </div>
                <div class="info-item">
                    <strong>Short URL:</strong>
                    <code id="successUrl"></code>
                </div>
            </div>
            <div class="modal-actions">
                <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
                <button type="button" id="createAnother" class="btn btn-primary">Create Another</button>
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
