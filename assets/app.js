/**
 * QR Code Manager - JavaScript
 * Handles all client-side interactions, QR generation, and API calls
 */

// Global QR Code instance
let qrCode = null;
let currentLogoData = null;

// ============================================
// Utility Functions
// ============================================

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type} show`;

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

/**
 * Show modal
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

/**
 * Hide modal
 */
function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

/**
 * Make API call
 */
async function apiCall(action, data = {}) {
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action, ...data })
        });

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'Network error occurred' };
    }
}

/**
 * Copy text to clipboard with enhanced feedback
 */
async function copyToClipboard(text, buttonElement = null) {
    try {
        await navigator.clipboard.writeText(text);

        // Visual feedback on button if provided
        if (buttonElement) {
            showCopyFeedback(buttonElement);
        }

        showToast('✓ Copied to clipboard!', 'success');
        return true;
    } catch (error) {
        // Fallback for older browsers
        try {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();

            const successful = document.execCommand('copy');
            document.body.removeChild(textarea);

            if (successful) {
                if (buttonElement) {
                    showCopyFeedback(buttonElement);
                }
                showToast('✓ Copied to clipboard!', 'success');
                return true;
            } else {
                showToast('Failed to copy', 'error');
                return false;
            }
        } catch (err) {
            console.error('Copy failed:', err);
            showToast('Copy not supported in this browser', 'error');
            return false;
        }
    }
}

/**
 * Show visual feedback on copy button
 */
function showCopyFeedback(button) {
    if (!button) return;

    // Store original content
    const originalContent = button.innerHTML;
    const originalClass = button.className;

    // Show success state
    button.innerHTML = '✓';
    button.classList.add('copied');

    // Reset after delay
    setTimeout(() => {
        button.innerHTML = originalContent;
        button.className = originalClass;
    }, 2000);
}

// ============================================
// QR Code Generation
// ============================================

/**
 * Get QR code options from form
 */
function getQrOptions(url) {
    return {
        width: parseInt(document.getElementById('qr_size')?.value || 300),
        height: parseInt(document.getElementById('qr_size')?.value || 300),
        type: 'svg',
        data: url,
        image: currentLogoData || undefined,
        margin: parseInt(document.getElementById('qr_margin')?.value || 10),
        qrOptions: {
            typeNumber: 0,
            mode: 'Byte',
            errorCorrectionLevel: 'Q'
        },
        imageOptions: {
            hideBackgroundDots: true,
            imageSize: 0.4,
            margin: 5,
            crossOrigin: 'anonymous'
        },
        dotsOptions: {
            color: document.getElementById('qr_dots_color')?.value || '#000000',
            type: document.getElementById('qr_dots_type')?.value || 'rounded'
        },
        backgroundOptions: {
            color: document.getElementById('qr_bg_color')?.value || '#ffffff'
        },
        cornersSquareOptions: {
            color: document.getElementById('qr_dots_color')?.value || '#000000',
            type: document.getElementById('qr_corners_type')?.value || 'square'
        },
        cornersDotOptions: {
            color: document.getElementById('qr_dots_color')?.value || '#000000',
            type: document.getElementById('qr_corner_dots_type')?.value || 'square'
        }
    };
}

/**
 * Generate QR code preview
 */
function generateQrPreview(url) {
    const previewContainer = document.getElementById('qrPreview');
    const placeholder = document.getElementById('previewPlaceholder');

    if (!url) {
        if (placeholder) placeholder.style.display = 'block';
        if (previewContainer) previewContainer.innerHTML = '';
        return;
    }

    if (placeholder) placeholder.style.display = 'none';

    // Clear previous QR code
    previewContainer.innerHTML = '';

    // Create new QR code
    const options = getQrOptions(url);
    qrCode = new QRCodeStyling(options);

    // Append to container
    qrCode.append(previewContainer);
}

/**
 * Download QR code in specified format
 */
function downloadQr(format) {
    if (!qrCode) {
        showToast('Please generate a QR code first', 'error');
        return;
    }

    const code = document.getElementById('editForm')?.dataset.code || 'qrcode';
    qrCode.download({
        name: `qr-${code}`,
        extension: format
    });

    showToast(`Downloading QR code as ${format.toUpperCase()}...`, 'success');
}

// ============================================
// Logo Upload Handling
// ============================================

/**
 * Handle logo file selection
 */
function handleLogoUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    // Validate file type
    if (!file.type.match('image/png') && !file.type.match('image/jpeg')) {
        showToast('Please upload a PNG or JPG image', 'error');
        return;
    }

    // Validate file size (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
        showToast('Logo file size must be less than 2MB', 'error');
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        currentLogoData = e.target.result;

        // Show preview
        const logoPreview = document.getElementById('logoPreview');
        const logoPreviewImage = document.getElementById('logoPreviewImage');

        if (logoPreview && logoPreviewImage) {
            logoPreviewImage.src = currentLogoData;
            logoPreview.style.display = 'flex';
        }

        // Update QR preview if URL is present
        const destinationUrl = document.getElementById('destination_url')?.value;
        if (destinationUrl) {
            updatePreview();
        }
    };

    reader.readAsDataURL(file);
}

/**
 * Remove logo
 */
function removeLogo() {
    currentLogoData = null;
    const logoInput = document.getElementById('qr_logo');
    const logoPreview = document.getElementById('logoPreview');

    if (logoInput) logoInput.value = '';
    if (logoPreview) logoPreview.style.display = 'none';

    // Update QR preview
    const destinationUrl = document.getElementById('destination_url')?.value;
    if (destinationUrl) {
        updatePreview();
    }
}

// ============================================
// Create Page
// ============================================

/**
 * Initialize create page
 */
function initCreatePage() {
    const form = document.getElementById('createForm');
    if (!form) return;

    // Update preview button
    const updateBtn = document.getElementById('updatePreview');
    if (updateBtn) {
        updateBtn.addEventListener('click', updatePreview);
    }

    // Auto-update preview on destination URL change
    const destinationInput = document.getElementById('destination_url');
    if (destinationInput) {
        destinationInput.addEventListener('input', debounce(updatePreview, 500));
    }

    // Logo upload
    const logoInput = document.getElementById('qr_logo');
    if (logoInput) {
        logoInput.addEventListener('change', handleLogoUpload);
    }

    // Remove logo button
    const removeLogoBtn = document.getElementById('removeLogo');
    if (removeLogoBtn) {
        removeLogoBtn.addEventListener('click', removeLogo);
    }

    // Form submission
    form.addEventListener('submit', handleCreateSubmit);

    // Initialize slug validation
    initSlugValidation('custom_slug', false);

    // Download buttons
    setupDownloadButtons();

    // Create Another button
    const createAnotherBtn = document.getElementById('createAnother');
    if (createAnotherBtn) {
        createAnotherBtn.addEventListener('click', () => {
            hideModal('successModal');
            form.reset();
            currentLogoData = null;
            document.getElementById('logoPreview').style.display = 'none';
            document.getElementById('qrPreview').innerHTML = '';
            document.getElementById('previewPlaceholder').style.display = 'block';
            document.getElementById('downloadOptions').style.display = 'none';
        });
    }
}

/**
 * Update preview
 */
function updatePreview() {
    const destinationUrl = document.getElementById('destination_url')?.value;
    if (!destinationUrl) {
        showToast('Please enter a destination URL', 'warning');
        return;
    }

    // Generate temporary short URL for preview
    const previewUrl = `${window.location.origin}/PREVIEW`;
    generateQrPreview(previewUrl);

    // Update short URL display
    const shortUrlElement = document.getElementById('shortUrl');
    if (shortUrlElement) {
        shortUrlElement.textContent = 'Will be generated after creation';
    }
}

/**
 * Handle create form submission
 */
async function handleCreateSubmit(event) {
    event.preventDefault();

    const form = event.target;
    const submitBtn = form.querySelector('[type="submit"]');

    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating...';

    // Get form data
    const formData = {
        title: document.getElementById('title').value,
        description: document.getElementById('description').value,
        destination_url: document.getElementById('destination_url').value,
        tags: document.getElementById('tags').value,
        custom_slug: document.getElementById('custom_slug')?.value || ''
    };

    // Create QR code via API
    const result = await apiCall('create', formData);

    if (result.success) {
        // Generate QR code image
        const qrUrl = result.data.qr_url;
        generateQrPreview(qrUrl);

        // Save QR code image to server (with version support)
        await saveQrImage(result.data.id, result.data.version_id);

        // Show success modal
        document.getElementById('successCode').textContent = result.data.code;
        document.getElementById('successUrl').textContent = result.data.qr_url;
        showModal('successModal');

        // Show download options
        document.getElementById('downloadOptions').style.display = 'block';

        showToast(result.message, 'success');
    } else {
        showToast(result.message || 'Failed to create QR code', 'error');
    }

    // Re-enable submit button
    submitBtn.disabled = false;
    submitBtn.textContent = 'Generate QR Code';
}

/**
 * Save QR code image to server
 */
async function saveQrImage(qrCodeId, versionId) {
    if (!qrCode) return;

    try {
        // Get blob from QR code
        const blob = await qrCode.getRawData('png');

        // Create form data
        const formData = new FormData();
        formData.append('image', blob, `v${versionId}.png`);
        formData.append('qr_code_id', qrCodeId);
        formData.append('version_id', versionId);

        // Upload to server
        const response = await fetch('save-image.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (!result.success) {
            console.error('Failed to save QR image:', result);
        }
    } catch (error) {
        console.error('Error saving QR image:', error);
    }
}

// ============================================
// Edit Page
// ============================================

/**
 * Initialize edit page
 */
function initEditPage() {
    const form = document.getElementById('editForm');
    if (!form) return;

    const code = form.dataset.code;

    // Generate initial preview
    const qrUrl = `${window.location.origin}/${code}`;
    generateQrPreview(qrUrl);

    // Update preview button
    const updateBtn = document.getElementById('updatePreview');
    if (updateBtn) {
        updateBtn.addEventListener('click', () => {
            generateQrPreview(qrUrl);
        });
    }

    // Logo upload
    const logoInput = document.getElementById('qr_logo');
    if (logoInput) {
        logoInput.addEventListener('change', handleLogoUpload);
    }

    // Remove logo button
    const removeLogoBtn = document.getElementById('removeLogo');
    if (removeLogoBtn) {
        removeLogoBtn.addEventListener('click', removeLogo);
    }

    // Form submission
    form.addEventListener('submit', handleEditSubmit);

    // Initialize slug validation
    initSlugValidation('new_slug', true);

    // Download buttons
    setupDownloadButtons();

    // Reset clicks button
    const resetBtn = document.getElementById('resetClicks');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            showModal('resetModal');
        });
    }

    // Reset confirmation
    const confirmResetBtn = document.getElementById('confirmReset');
    if (confirmResetBtn) {
        confirmResetBtn.addEventListener('click', handleResetClicks);
    }

    const cancelResetBtn = document.getElementById('cancelReset');
    if (cancelResetBtn) {
        cancelResetBtn.addEventListener('click', () => {
            hideModal('resetModal');
        });
    }

    // Close success modal
    const closeSuccessBtn = document.getElementById('closeSuccess');
    if (closeSuccessBtn) {
        closeSuccessBtn.addEventListener('click', () => {
            hideModal('successModal');
        });
    }

    // Initialize version gallery
    initVersionGallery();
}

/**
 * Handle edit form submission
 */
async function handleEditSubmit(event) {
    event.preventDefault();

    const form = event.target;
    const submitBtn = form.querySelector('[type="submit"]');
    const qrId = form.dataset.id;
    const code = form.dataset.code;

    // Check if slug changed
    const newSlugInput = document.getElementById('new_slug');
    const newSlug = newSlugInput?.value || code;
    const slugChanged = newSlug !== code;
    const clicks = parseInt(newSlugInput?.dataset.clicks) || 0;

    // If slug changed and has clicks, ask for confirmation
    if (slugChanged && clicks > 0) {
        const confirmed = confirm(
            `⚠️ Warning: This QR code has ${clicks} click${clicks !== 1 ? 's' : ''}.\n\n` +
            `Changing the slug from "${code}" to "${newSlug}" will break existing QR codes that have been printed or distributed.\n\n` +
            `Are you sure you want to continue?`
        );

        if (!confirmed) {
            return; // User cancelled
        }
    }

    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.textContent = 'Updating...';

    // Get form data
    const formData = {
        id: parseInt(qrId),
        title: document.getElementById('title').value,
        description: document.getElementById('description').value,
        destination_url: document.getElementById('destination_url').value,
        tags: document.getElementById('tags').value
    };

    // Include new slug if changed
    if (slugChanged) {
        formData.new_slug = newSlug;
        formData.confirm_slug_change = true;
    }

    // Update QR code via API
    const result = await apiCall('update', formData);

    if (result.success) {
        // Note: Metadata update only - image regeneration moved to version creation
        // To create a new styled version, use "Create New Version" button (Phase 4)

        // Show success modal
        showModal('successModal');
        showToast(result.message, 'success');

        // If slug changed, redirect to updated URL after a delay
        if (slugChanged) {
            setTimeout(() => {
                window.location.href = `edit.php?id=${qrId}`;
            }, 1500);
        }
    } else {
        showToast(result.message || 'Failed to update QR code', 'error');
    }

    // Re-enable submit button
    submitBtn.disabled = false;
    submitBtn.textContent = 'Update QR Code';
}

/**
 * Handle reset clicks
 */
async function handleResetClicks() {
    const form = document.getElementById('editForm');
    const qrId = form.dataset.id;

    hideModal('resetModal');

    const result = await apiCall('reset_clicks', { id: parseInt(qrId) });

    if (result.success) {
        showToast(result.message, 'success');
        // Reload page to show updated count
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    } else {
        showToast(result.message || 'Failed to reset clicks', 'error');
    }
}

// ============================================
// Version Gallery (Edit Page)
// ============================================

/**
 * Initialize version gallery
 */
async function initVersionGallery() {
    const galleryContainer = document.getElementById('versionGallery');
    if (!galleryContainer) return;

    const form = document.getElementById('editForm');
    const qrCodeId = parseInt(form.dataset.id);

    // Setup create version button
    const createVersionBtn = document.getElementById('createVersionBtn');
    if (createVersionBtn) {
        createVersionBtn.addEventListener('click', () => {
            showModal('createVersionModal');
        });
    }

    // Setup create version form
    const createVersionForm = document.getElementById('createVersionForm');
    if (createVersionForm) {
        createVersionForm.addEventListener('submit', handleCreateVersion);
    }

    // Load versions
    await loadVersions(qrCodeId);
}

/**
 * Load and display versions
 */
async function loadVersions(qrCodeId) {
    const galleryContainer = document.getElementById('versionGallery');
    if (!galleryContainer) return;

    try {
        // Call versions API
        const response = await fetch('api-versions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_versions',
                qr_code_id: qrCodeId,
                limit: 5  // Show first 5 in gallery
            })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Failed to load versions');
        }

        const versions = result.data.versions;
        const totalCount = result.data.total_count;

        // Clear loading message
        galleryContainer.innerHTML = '';

        if (versions.length === 0) {
            galleryContainer.innerHTML = '<div class="version-gallery-loading">No versions yet. Create your first version!</div>';
            return;
        }

        // Render version cards
        versions.forEach(version => {
            const card = createVersionCard(version, qrCodeId);
            galleryContainer.appendChild(card);
        });

        // Show "View All" link if more than 5 versions
        const viewAllContainer = document.getElementById('viewAllVersions');
        if (viewAllContainer && totalCount > 5) {
            viewAllContainer.style.display = 'block';
        }

    } catch (error) {
        console.error('Error loading versions:', error);
        galleryContainer.innerHTML = '<div class="version-gallery-loading">Failed to load versions</div>';
        showToast('Failed to load versions', 'error');
    }
}

/**
 * Create version card element
 */
function createVersionCard(version, qrCodeId) {
    const card = document.createElement('div');
    card.className = 'version-card' + (version.is_favorite ? ' is-favorite' : '');
    card.dataset.versionId = version.id;

    const isFavorite = version.is_favorite;
    const createdDate = new Date(version.created_at).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });

    card.innerHTML = `
        <div class="version-card-header">
            ${isFavorite ? '<span class="version-favorite-badge">⭐ Favorite</span>' : '<span></span>'}
        </div>
        <div class="version-image">
            <img src="${version.image_url}?t=${Date.now()}" alt="${version.version_name}" loading="lazy">
        </div>
        <div class="version-name" title="${version.version_name}">${version.version_name}</div>
        <div class="version-date">${createdDate}</div>
        <div class="version-actions">
            ${!isFavorite ? `<button class="version-action-btn primary" onclick="setVersionAsFavorite(${version.id})">Set Favorite</button>` : ''}
            <button class="version-action-btn" onclick="downloadVersion(${qrCodeId}, ${version.id})">Download</button>
            <button class="version-action-btn danger" onclick="deleteVersionConfirm(${version.id}, ${isFavorite})">Delete</button>
        </div>
    `;

    return card;
}

/**
 * Handle create version form submission
 */
async function handleCreateVersion(event) {
    event.preventDefault();

    const form = event.target;
    const editForm = document.getElementById('editForm');
    const qrCodeId = parseInt(editForm.dataset.id);

    const versionName = document.getElementById('version_name').value.trim();
    const startFrom = document.querySelector('input[name="start_from"]:checked').value;

    const submitBtn = form.querySelector('[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating...';

    try {
        const requestData = {
            action: 'create_version',
            qr_code_id: qrCodeId,
            version_name: versionName || undefined
        };

        // If cloning current version, add clone_from_version_id
        if (startFrom === 'current') {
            const versions = await getCurrentVersions(qrCodeId);
            const favoriteVersion = versions.find(v => v.is_favorite);
            if (favoriteVersion) {
                requestData.clone_from_version_id = favoriteVersion.id;
            }
        } else {
            // If starting from default, capture current form styling
            requestData.style_config = {
                width: parseInt(document.getElementById('qr_size')?.value || 300),
                height: parseInt(document.getElementById('qr_size')?.value || 300),
                margin: parseInt(document.getElementById('qr_margin')?.value || 10),
                dotsOptions: {
                    color: document.getElementById('qr_dots_color')?.value || '#000000',
                    type: document.getElementById('qr_dots_type')?.value || 'rounded'
                },
                backgroundOptions: {
                    color: document.getElementById('qr_bg_color')?.value || '#ffffff'
                },
                cornersSquareOptions: {
                    color: document.getElementById('qr_dots_color')?.value || '#000000',
                    type: document.getElementById('qr_corners_type')?.value || 'square'
                },
                cornersDotOptions: {
                    color: document.getElementById('qr_dots_color')?.value || '#000000',
                    type: document.getElementById('qr_corner_dots_type')?.value || 'square'
                }
            };
        }

        const response = await fetch('api-versions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestData)
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Failed to create version');
        }

        // Generate QR code preview with new version's URL
        const code = editForm.dataset.code;
        const qrUrl = `${window.location.origin}/${code}`;
        generateQrPreview(qrUrl);

        // Small delay to ensure QR code is rendered
        await new Promise(resolve => setTimeout(resolve, 100));

        // Save QR image
        await saveQrImage(qrCodeId, result.data.version_id);

        // Small delay to ensure file is written before reloading
        await new Promise(resolve => setTimeout(resolve, 200));

        // Reload versions gallery
        await loadVersions(qrCodeId);

        // Hide modal and reset form
        hideModal('createVersionModal');
        form.reset();

        showToast('Version created successfully!', 'success');

    } catch (error) {
        console.error('Error creating version:', error);
        showToast(error.message || 'Failed to create version', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Create Version';
    }
}

/**
 * Get current versions (helper function)
 */
async function getCurrentVersions(qrCodeId) {
    const response = await fetch('api-versions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'get_versions',
            qr_code_id: qrCodeId
        })
    });
    const result = await response.json();
    return result.success ? result.data.versions : [];
}

/**
 * Set version as favorite
 */
async function setVersionAsFavorite(versionId) {
    try {
        const response = await fetch('api-versions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'set_favorite',
                version_id: versionId
            })
        });

        // Check if response is OK
        if (!response.ok) {
            const text = await response.text();
            console.error('Server response:', text);
            throw new Error(`Server error: ${response.status} ${response.statusText}`);
        }

        // Try to parse JSON
        const text = await response.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('Failed to parse JSON. Response text:', text);
            throw new Error('Invalid JSON response from server');
        }

        if (!result.success) {
            throw new Error(result.message || 'Failed to set favorite');
        }

        // Reload versions gallery
        const form = document.getElementById('editForm');
        const qrCodeId = parseInt(form.dataset.id);
        await loadVersions(qrCodeId);

        showToast('Favorite version updated!', 'success');

    } catch (error) {
        console.error('Error setting favorite:', error);
        showToast(error.message || 'Failed to set favorite', 'error');
    }
}

/**
 * Download version
 */
function downloadVersion(qrCodeId, versionId) {
    const downloadUrl = `generated/qr-code-${qrCodeId}/v${versionId}.png`;
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = `qr-code-v${versionId}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    showToast('Download started', 'success');
}

/**
 * Confirm delete version
 */
function deleteVersionConfirm(versionId, isFavorite) {
    // Get version name from DOM
    const versionCard = document.querySelector(`.version-card[data-version-id="${versionId}"]`);
    const versionName = versionCard ? versionCard.querySelector('.version-name').textContent : 'this version';

    // Show delete modal
    document.getElementById('deleteVersionName').innerHTML = `<strong>${versionName}</strong>`;

    if (isFavorite) {
        document.getElementById('deleteVersionName').innerHTML += '<br><span style="color: var(--warning-color);">⚠️ This is your favorite version. A new favorite will be automatically selected.</span>';
    }

    showModal('deleteVersionModal');

    // Setup confirm button
    const confirmBtn = document.getElementById('confirmDeleteVersion');
    confirmBtn.onclick = () => deleteVersion(versionId);
}

/**
 * Delete version
 */
async function deleteVersion(versionId) {
    hideModal('deleteVersionModal');

    try {
        const response = await fetch('api-versions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete_version',
                version_id: versionId
            })
        });

        const result = await response.json();

        if (!result.success) {
            // Check if new favorite is required
            if (result.data && result.data.requires_new_favorite) {
                const otherVersions = result.data.other_versions || [];
                if (otherVersions.length > 0) {
                    // Automatically select first other version as new favorite
                    const newFavoriteId = otherVersions[0].id;

                    // Retry delete with new favorite
                    const retryResponse = await fetch('api-versions.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'delete_version',
                            version_id: versionId,
                            new_favorite_id: newFavoriteId
                        })
                    });

                    const retryResult = await retryResponse.json();
                    if (!retryResult.success) {
                        throw new Error(retryResult.message);
                    }
                } else {
                    throw new Error('Cannot delete the last version');
                }
            } else {
                throw new Error(result.message || 'Failed to delete version');
            }
        }

        // Reload versions gallery
        const form = document.getElementById('editForm');
        const qrCodeId = parseInt(form.dataset.id);
        await loadVersions(qrCodeId);

        showToast('Version deleted successfully', 'success');

    } catch (error) {
        console.error('Error deleting version:', error);
        showToast(error.message || 'Failed to delete version', 'error');
    }
}

// ============================================
// Dashboard Page
// ============================================

/**
 * Initialize dashboard page
 */
function initDashboardPage() {
    // Delete buttons
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const qrId = btn.dataset.id;
            const qrCode = btn.dataset.code;

            document.getElementById('deleteCode').textContent = qrCode;
            showModal('deleteModal');

            // Set up delete confirmation
            const confirmBtn = document.getElementById('confirmDelete');
            confirmBtn.onclick = () => handleDelete(qrId);
        });
    });

    // Cancel delete button
    const cancelDeleteBtn = document.getElementById('cancelDelete');
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', () => {
            hideModal('deleteModal');
        });
    }

    // Copy buttons
    const copyButtons = document.querySelectorAll('.btn-copy');
    copyButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent triggering row click
            const url = btn.dataset.url;
            copyToClipboard(url, btn);
        });
    });

    // Initialize search functionality
    initSearch();

    // Initialize sorting functionality
    initSorting();

    // Initialize pagination
    initPagination();

    // Initialize QR preview modal
    initQrPreviewModal();
}

// ============================================
// Search & Filter
// ============================================

/**
 * Initialize search functionality
 */
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    const clearButton = document.getElementById('clearSearch');
    const resultsCount = document.getElementById('resultsCount');
    const tableBody = document.getElementById('qrTableBody');
    const noResultsRow = document.getElementById('noResultsRow');

    if (!searchInput || !tableBody) return;

    // Get all data rows (excluding the no results row)
    const getAllRows = () => {
        return Array.from(tableBody.querySelectorAll('tr')).filter(row => row.id !== 'noResultsRow');
    };

    const totalRows = getAllRows().length;

    /**
     * Perform search/filter
     */
    function performSearch() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const rows = getAllRows();

        // Show clear button if search has text
        if (searchTerm) {
            clearButton.style.display = 'flex';
        } else {
            clearButton.style.display = 'none';
        }

        let visibleCount = 0;

        rows.forEach(row => {
            if (!searchTerm) {
                // No search term - show all
                row.style.display = '';
                visibleCount++;
            } else {
                // Check if row matches search term
                const title = row.dataset.title || '';
                const code = row.dataset.code || '';
                const destination = row.dataset.destination || '';
                const tags = row.dataset.tags || '';

                const matches = title.includes(searchTerm) ||
                               code.includes(searchTerm) ||
                               destination.includes(searchTerm) ||
                               tags.includes(searchTerm);

                if (matches) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }
        });

        // Update results count
        if (searchTerm) {
            resultsCount.textContent = `Found ${visibleCount} of ${totalRows} QR code${visibleCount !== 1 ? 's' : ''}`;
        } else {
            resultsCount.textContent = `Showing all ${totalRows} QR code${totalRows !== 1 ? 's' : ''}`;
        }

        // Show/hide no results row
        if (visibleCount === 0 && searchTerm) {
            noResultsRow.style.display = '';
        } else {
            noResultsRow.style.display = 'none';
        }

        // Update pagination when search changes
        if (typeof resetPagination === 'function') {
            resetPagination();
        }
    }

    /**
     * Clear search
     */
    function clearSearch() {
        searchInput.value = '';
        searchInput.focus();
        performSearch();
    }

    // Event listeners
    searchInput.addEventListener('input', performSearch);
    clearButton.addEventListener('click', clearSearch);

    // Allow ESC key to clear search
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            clearSearch();
        }
    });
}

// ============================================
// Column Sorting
// ============================================

/**
 * Initialize column sorting
 */
function initSorting() {
    const sortableHeaders = document.querySelectorAll('.sortable');
    const tableBody = document.getElementById('qrTableBody');

    if (!sortableHeaders.length || !tableBody) return;

    // Current sort state
    let currentSort = {
        column: sessionStorage.getItem('sortColumn') || null,
        direction: sessionStorage.getItem('sortDirection') || 'asc'
    };

    // Restore sort state from session
    if (currentSort.column) {
        applySortState(currentSort.column, currentSort.direction);
        sortTable(currentSort.column, currentSort.direction);
    }

    /**
     * Apply visual sort state to headers
     */
    function applySortState(column, direction) {
        sortableHeaders.forEach(header => {
            header.classList.remove('active', 'asc', 'desc');
            if (header.dataset.sort === column) {
                header.classList.add('active', direction);
            }
        });
    }

    /**
     * Sort table by column
     */
    function sortTable(column, direction) {
        const rows = Array.from(tableBody.querySelectorAll('tr')).filter(row => row.id !== 'noResultsRow');

        rows.sort((a, b) => {
            let aValue, bValue;

            switch (column) {
                case 'title':
                    aValue = a.dataset.title || '';
                    bValue = b.dataset.title || '';
                    break;
                case 'code':
                    aValue = a.dataset.code || '';
                    bValue = b.dataset.code || '';
                    break;
                case 'clicks':
                    aValue = parseInt(a.dataset.clicks || 0);
                    bValue = parseInt(b.dataset.clicks || 0);
                    break;
                case 'created':
                    aValue = new Date(a.dataset.created || 0);
                    bValue = new Date(b.dataset.created || 0);
                    break;
                default:
                    return 0;
            }

            // Compare values
            let comparison = 0;
            if (typeof aValue === 'number') {
                comparison = aValue - bValue;
            } else if (aValue instanceof Date) {
                comparison = aValue.getTime() - bValue.getTime();
            } else {
                comparison = aValue.localeCompare(bValue);
            }

            return direction === 'asc' ? comparison : -comparison;
        });

        // Re-append rows in sorted order
        rows.forEach(row => tableBody.appendChild(row));

        // Keep no results row at the end
        const noResultsRow = document.getElementById('noResultsRow');
        if (noResultsRow) {
            tableBody.appendChild(noResultsRow);
        }
    }

    /**
     * Handle header click
     */
    sortableHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const column = header.dataset.sort;

            // Toggle direction if same column, else default to ascending
            let direction = 'asc';
            if (currentSort.column === column && currentSort.direction === 'asc') {
                direction = 'desc';
            }

            // Update state
            currentSort = { column, direction };
            sessionStorage.setItem('sortColumn', column);
            sessionStorage.setItem('sortDirection', direction);

            // Apply sort
            applySortState(column, direction);
            sortTable(column, direction);
        });
    });
}

// ============================================
// Pagination
// ============================================

let paginationState = {
    currentPage: 1,
    pageSize: 25,
    totalItems: 0
};

/**
 * Initialize pagination
 */
function initPagination() {
    const tableBody = document.getElementById('qrTableBody');
    const pageSizeSelect = document.getElementById('pageSize');
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');

    if (!tableBody) return;

    // Restore page size from sessionStorage
    const savedPageSize = sessionStorage.getItem('pageSize');
    if (savedPageSize) {
        paginationState.pageSize = parseInt(savedPageSize);
        if (pageSizeSelect) {
            pageSizeSelect.value = savedPageSize;
        }
    }

    // Page size change handler
    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', () => {
            paginationState.pageSize = parseInt(pageSizeSelect.value);
            paginationState.currentPage = 1;
            sessionStorage.setItem('pageSize', paginationState.pageSize);
            updatePagination();
        });
    }

    // Previous button
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (paginationState.currentPage > 1) {
                paginationState.currentPage--;
                updatePagination();
            }
        });
    }

    // Next button
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            const totalPages = Math.ceil(paginationState.totalItems / paginationState.pageSize);
            if (paginationState.currentPage < totalPages) {
                paginationState.currentPage++;
                updatePagination();
            }
        });
    }

    // Initial pagination
    updatePagination();
}

/**
 * Update pagination display and visible rows
 */
function updatePagination() {
    const tableBody = document.getElementById('qrTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const pageNumbersContainer = document.getElementById('pageNumbers');
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');

    if (!tableBody) return;

    // Smooth scroll to top of table on page change
    const tableContainer = document.querySelector('.table-container');
    if (tableContainer && paginationState.currentPage > 1) {
        tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Get all visible rows (after search/filter)
    const allRows = Array.from(tableBody.querySelectorAll('tr')).filter(row => {
        return row.id !== 'noResultsRow' && row.style.display !== 'none';
    });

    paginationState.totalItems = allRows.length;
    const totalPages = Math.ceil(paginationState.totalItems / paginationState.pageSize);

    // Ensure current page is valid
    if (paginationState.currentPage > totalPages && totalPages > 0) {
        paginationState.currentPage = totalPages;
    }
    if (paginationState.currentPage < 1) {
        paginationState.currentPage = 1;
    }

    const startIdx = (paginationState.currentPage - 1) * paginationState.pageSize;
    const endIdx = startIdx + paginationState.pageSize;

    // Show/hide rows based on current page
    allRows.forEach((row, idx) => {
        if (idx >= startIdx && idx < endIdx) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });

    // Update pagination info
    if (paginationInfo) {
        const showingStart = paginationState.totalItems > 0 ? startIdx + 1 : 0;
        const showingEnd = Math.min(endIdx, paginationState.totalItems);
        paginationInfo.textContent = `Showing ${showingStart}-${showingEnd} of ${paginationState.totalItems}`;
    }

    // Update prev/next buttons
    if (prevBtn) {
        prevBtn.disabled = paginationState.currentPage <= 1;
    }
    if (nextBtn) {
        nextBtn.disabled = paginationState.currentPage >= totalPages;
    }

    // Update page numbers
    if (pageNumbersContainer) {
        renderPageNumbers(pageNumbersContainer, paginationState.currentPage, totalPages);
    }
}

/**
 * Render page number buttons
 */
function renderPageNumbers(container, currentPage, totalPages) {
    container.innerHTML = '';

    if (totalPages <= 1) return;

    const maxButtons = 7;
    let startPage, endPage;

    if (totalPages <= maxButtons) {
        // Show all pages
        startPage = 1;
        endPage = totalPages;
    } else {
        // Smart pagination with ellipsis
        const halfButtons = Math.floor(maxButtons / 2);

        if (currentPage <= halfButtons) {
            startPage = 1;
            endPage = maxButtons - 1;
        } else if (currentPage >= totalPages - halfButtons) {
            startPage = totalPages - maxButtons + 2;
            endPage = totalPages;
        } else {
            startPage = currentPage - halfButtons + 1;
            endPage = currentPage + halfButtons - 1;
        }
    }

    // First page button
    if (startPage > 1) {
        addPageButton(container, 1, currentPage);
        if (startPage > 2) {
            addEllipsis(container);
        }
    }

    // Page number buttons
    for (let i = startPage; i <= endPage; i++) {
        addPageButton(container, i, currentPage);
    }

    // Last page button
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            addEllipsis(container);
        }
        addPageButton(container, totalPages, currentPage);
    }
}

/**
 * Add page button
 */
function addPageButton(container, pageNum, currentPage) {
    const button = document.createElement('button');
    button.className = 'page-number' + (pageNum === currentPage ? ' active' : '');
    button.textContent = pageNum;
    button.addEventListener('click', () => {
        paginationState.currentPage = pageNum;
        updatePagination();
    });
    container.appendChild(button);
}

/**
 * Add ellipsis
 */
function addEllipsis(container) {
    const ellipsis = document.createElement('span');
    ellipsis.className = 'page-number ellipsis';
    ellipsis.textContent = '...';
    container.appendChild(ellipsis);
}

/**
 * Reset pagination to first page (used by search)
 */
function resetPagination() {
    paginationState.currentPage = 1;
    updatePagination();
}

// ============================================
// QR Preview Modal
// ============================================

/**
 * Initialize QR preview modal
 */
function initQrPreviewModal() {
    const modal = document.getElementById('qrPreviewModal');
    const closeBtn = document.getElementById('closePreviewModal');

    if (!modal) return;

    // Close button
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            hideModal('qrPreviewModal');
        });
    }

    // Make QR preview images clickable
    const qrPreviews = document.querySelectorAll('.qr-preview');
    qrPreviews.forEach(img => {
        img.style.cursor = 'pointer';
        img.addEventListener('click', () => {
            const row = img.closest('tr');
            if (row) {
                showQrPreview(row);
            }
        });
    });

    // ESC key to close
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            hideModal('qrPreviewModal');
        }
    });
}

/**
 * Show QR preview modal with data from row
 */
function showQrPreview(row) {
    const qrId = row.dataset.id;
    const code = row.dataset.code.toUpperCase();
    const title = row.querySelector('.title-cell strong')?.textContent || '';
    const description = row.querySelector('.title-cell small')?.textContent || '';
    const destination = row.dataset.destination;
    const clicks = row.dataset.clicks;
    const created = row.dataset.created;
    const tags = row.dataset.tags;

    // Populate modal
    document.getElementById('previewModalImage').src = `generated/${code}.png`;
    document.getElementById('previewModalTitle').textContent = title;
    document.getElementById('previewModalCode').textContent = code;

    const qrUrl = window.location.origin + '/' + code;
    document.getElementById('previewModalUrl').textContent = qrUrl;

    const destinationLink = document.getElementById('previewModalDestination');
    // Get actual destination from row
    const actualDestUrl = row.querySelector('.destination-url')?.href || '';
    destinationLink.href = actualDestUrl;
    destinationLink.textContent = actualDestUrl;

    // Description (show/hide)
    const descGroup = document.getElementById('previewDescriptionGroup');
    if (description && description.trim()) {
        document.getElementById('previewModalDescription').textContent = description;
        descGroup.style.display = 'block';
    } else {
        descGroup.style.display = 'none';
    }

    // Tags (show/hide)
    const tagsGroup = document.getElementById('previewTagsGroup');
    const tagsContainer = document.getElementById('previewModalTags');
    if (tags && tags.trim()) {
        tagsContainer.innerHTML = '';
        tags.split(',').forEach(tag => {
            const tagEl = document.createElement('span');
            tagEl.className = 'tag';
            tagEl.textContent = tag.trim();
            tagsContainer.appendChild(tagEl);
        });
        tagsGroup.style.display = 'block';
    } else {
        tagsGroup.style.display = 'none';
    }

    // Stats
    document.getElementById('previewModalClicks').textContent = parseInt(clicks).toLocaleString();
    document.getElementById('previewModalCreated').textContent = formatDateShort(created);

    // Action buttons
    document.getElementById('previewEditBtn').href = `edit.php?id=${qrId}`;
    document.getElementById('previewDownloadBtn').href = `generated/${code}.png`;
    document.getElementById('previewDownloadBtn').download = `qr-${code}.png`;

    // Copy buttons
    const copyCodeBtn = document.getElementById('previewCopyCode');
    const copyUrlBtn = document.getElementById('previewCopyUrl');
    copyCodeBtn.onclick = (e) => {
        e.preventDefault();
        copyToClipboard(code, copyCodeBtn);
    };
    copyUrlBtn.onclick = (e) => {
        e.preventDefault();
        copyToClipboard(qrUrl, copyUrlBtn);
    };

    // Delete button
    document.getElementById('previewDeleteBtn').onclick = () => {
        hideModal('qrPreviewModal');
        // Trigger existing delete modal
        document.getElementById('deleteCode').textContent = code;
        showModal('deleteModal');
        const confirmBtn = document.getElementById('confirmDelete');
        confirmBtn.onclick = () => handleDelete(qrId);
    };

    // Show modal
    showModal('qrPreviewModal');
}

/**
 * Format date for preview (shorter version)
 */
function formatDateShort(datetime) {
    if (!datetime) return '-';
    const date = new Date(datetime);
    const now = new Date();
    const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Today';
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return `${diffDays} days ago`;
    if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;

    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

/**
 * Handle QR code deletion
 */
async function handleDelete(qrId) {
    hideModal('deleteModal');

    const result = await apiCall('delete', { id: parseInt(qrId) });

    if (result.success) {
        showToast(result.message, 'success');

        // Remove row from table with animation
        const row = document.querySelector(`tr[data-id="${qrId}"]`);
        if (row) {
            row.style.transition = 'opacity 300ms';
            row.style.opacity = '0';
            setTimeout(() => {
                row.remove();

                // Check if table is empty
                const tbody = document.querySelector('.qr-table tbody');
                if (tbody && tbody.children.length === 0) {
                    window.location.reload();
                }
            }, 300);
        }
    } else {
        showToast(result.message || 'Failed to delete QR code', 'error');
    }
}

// ============================================
// Download Buttons Setup
// ============================================

function setupDownloadButtons() {
    const downloadPng = document.getElementById('downloadPng');
    const downloadSvg = document.getElementById('downloadSvg');
    const downloadJpg = document.getElementById('downloadJpg');

    if (downloadPng) {
        downloadPng.addEventListener('click', () => downloadQr('png'));
    }

    if (downloadSvg) {
        downloadSvg.addEventListener('click', () => downloadQr('svg'));
    }

    if (downloadJpg) {
        downloadJpg.addEventListener('click', () => downloadQr('jpeg'));
    }
}

// ============================================
// Utility: Debounce
// ============================================

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ============================================
// Slug Validation
// ============================================

let slugCheckInProgress = false;

/**
 * Check slug availability via API
 */
async function checkSlugAvailability(slug, excludeId = null) {
    if (!slug || slug.trim() === '') {
        return { available: true, reason: '', suggestions: [] };
    }

    slugCheckInProgress = true;

    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'check_slug_availability',
                slug: slug.trim(),
                exclude_id: excludeId
            })
        });

        const data = await response.json();
        slugCheckInProgress = false;

        if (data.success && data.data) {
            return data.data;
        }

        return { available: false, reason: data.message, suggestions: [] };
    } catch (error) {
        console.error('Slug check error:', error);
        slugCheckInProgress = false;
        return { available: false, reason: 'Error checking availability', suggestions: [] };
    }
}

/**
 * Show slug feedback (available/taken/invalid)
 */
function showSlugFeedback(result, feedbackEl, suggestionsEl) {
    if (!feedbackEl) return;

    if (result.available) {
        feedbackEl.className = 'slug-feedback success';
        feedbackEl.innerHTML = '✓ Available';
        if (suggestionsEl) {
            suggestionsEl.style.display = 'none';
        }
    } else {
        feedbackEl.className = 'slug-feedback error';
        feedbackEl.innerHTML = '✗ ' + (result.reason || 'Not available');

        // Show suggestions if available
        if (suggestionsEl && result.suggestions && result.suggestions.length > 0) {
            suggestionsEl.style.display = 'block';
            suggestionsEl.innerHTML = '<strong>Suggestions:</strong> ' +
                result.suggestions.map(s =>
                    `<button type="button" class="suggestion-btn" data-slug="${s}">${s}</button>`
                ).join(' ');

            // Handle suggestion clicks
            suggestionsEl.querySelectorAll('.suggestion-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const slugInput = document.getElementById('custom_slug') || document.getElementById('new_slug');
                    if (slugInput) {
                        slugInput.value = e.target.dataset.slug;
                        slugInput.dispatchEvent(new Event('input'));
                    }
                });
            });
        } else if (suggestionsEl) {
            suggestionsEl.style.display = 'none';
        }
    }
}

/**
 * Update character counter
 */
function updateCharCounter(input, counterEl) {
    if (!counterEl) return;
    const length = input.value.length;
    counterEl.textContent = `${length}/33`;
}

/**
 * Initialize slug validation for create/edit pages
 */
function initSlugValidation(inputId, isEditMode = false) {
    const slugInput = document.getElementById(inputId);
    if (!slugInput) return;

    const feedbackEl = document.getElementById('slugFeedback');
    const suggestionsEl = document.getElementById('slugSuggestions');
    const counterEl = document.getElementById('slugCharCounter');
    const autoGenerateBtn = document.getElementById('autoGenerateBtn');
    const warningEl = document.getElementById('slugWarning');

    // Double-click to edit in edit mode (slug starts locked/readonly)
    if (isEditMode && slugInput.classList.contains('slug-locked')) {
        // Double-click handler
        slugInput.addEventListener('dblclick', () => {
            // Unlock the field
            slugInput.classList.remove('slug-locked');
            slugInput.removeAttribute('readonly');
            slugInput.focus();
            slugInput.select();

            // Enable the auto-generate button
            if (autoGenerateBtn) {
                autoGenerateBtn.disabled = false;
                autoGenerateBtn.classList.remove('slug-btn-locked');
                autoGenerateBtn.title = 'Auto-generate random code';
            }

            // Show visual feedback
            if (feedbackEl) {
                feedbackEl.className = 'slug-feedback success';
                feedbackEl.innerHTML = '✏️ Editing enabled';
                setTimeout(() => {
                    feedbackEl.className = 'slug-feedback';
                    feedbackEl.innerHTML = '';
                }, 2000);
            }
        });

        // Single click to show instruction
        slugInput.addEventListener('click', (e) => {
            if (slugInput.classList.contains('slug-locked') && feedbackEl) {
                feedbackEl.className = 'slug-feedback';
                feedbackEl.innerHTML = '💡 Double-click to edit this field';
                setTimeout(() => {
                    feedbackEl.className = 'slug-feedback';
                    feedbackEl.innerHTML = '';
                }, 2000);
            }
        });
    }

    // Debounced slug check
    const debouncedCheck = debounce(async () => {
        const slug = slugInput.value.trim();

        if (slug === '') {
            if (feedbackEl) {
                feedbackEl.className = 'slug-feedback';
                feedbackEl.innerHTML = '';
            }
            if (suggestionsEl) {
                suggestionsEl.style.display = 'none';
            }
            return;
        }

        // Show loading state
        if (feedbackEl) {
            feedbackEl.className = 'slug-feedback';
            feedbackEl.innerHTML = '⋯ Checking...';
        }

        const excludeId = isEditMode ? parseInt(slugInput.closest('form')?.dataset.id) : null;
        const result = await checkSlugAvailability(slug, excludeId);
        showSlugFeedback(result, feedbackEl, suggestionsEl);
    }, 500);

    // Input event listener
    slugInput.addEventListener('input', () => {
        updateCharCounter(slugInput, counterEl);
        debouncedCheck();

        // Show warning in edit mode if slug changed and has clicks
        if (isEditMode && warningEl) {
            const original = slugInput.dataset.original;
            const clicks = parseInt(slugInput.dataset.clicks) || 0;
            if (slugInput.value !== original && clicks > 0) {
                warningEl.style.display = 'block';
            } else {
                warningEl.style.display = 'none';
            }
        }
    });

    // Auto-generate button
    if (autoGenerateBtn) {
        autoGenerateBtn.addEventListener('click', async () => {
            autoGenerateBtn.disabled = true;
            autoGenerateBtn.innerHTML = '⋯';

            // Generate random code (6 characters)
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            let code = '';
            for (let i = 0; i < 6; i++) {
                code += chars[Math.floor(Math.random() * chars.length)];
            }

            slugInput.value = code;
            updateCharCounter(slugInput, counterEl);

            // Check availability
            const excludeId = isEditMode ? parseInt(slugInput.closest('form')?.dataset.id) : null;
            const result = await checkSlugAvailability(code, excludeId);
            showSlugFeedback(result, feedbackEl, suggestionsEl);

            autoGenerateBtn.disabled = false;
            autoGenerateBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13.65 2.35C12.2 0.9 10.21 0 8 0C3.58 0 0.01 3.58 0.01 8C0.01 12.42 3.58 16 8 16C11.73 16 14.84 13.45 15.73 10H13.65C12.83 12.33 10.61 14 8 14C4.69 14 2 11.31 2 8C2 4.69 4.69 2 8 2C9.66 2 11.14 2.69 12.22 3.78L9 7H16V0L13.65 2.35Z" fill="currentColor"/></svg> Auto';

            // Show warning in edit mode
            if (isEditMode && warningEl) {
                const original = slugInput.dataset.original;
                const clicks = parseInt(slugInput.dataset.clicks) || 0;
                if (code !== original && clicks > 0) {
                    warningEl.style.display = 'block';
                }
            }
        });
    }

    // Initialize counter
    updateCharCounter(slugInput, counterEl);
}

// ============================================
// Page Initialization
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    // Determine which page we're on and initialize accordingly
    if (document.getElementById('createForm')) {
        initCreatePage();
    } else if (document.getElementById('editForm')) {
        initEditPage();
    } else if (document.querySelector('.qr-table')) {
        initDashboardPage();
    }

    // Close modals when clicking outside
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });
});
