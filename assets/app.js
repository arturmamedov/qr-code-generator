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
 * Copy text to clipboard
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showToast('Copied to clipboard!', 'success');
    } catch (error) {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showToast('Copied to clipboard!', 'success');
    }
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
        tags: document.getElementById('tags').value
    };

    // Create QR code via API
    const result = await apiCall('create', formData);

    if (result.success) {
        // Generate QR code image
        const qrUrl = result.data.qr_url;
        generateQrPreview(qrUrl);

        // Save QR code image to server
        await saveQrImage(result.data.code);

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
async function saveQrImage(code) {
    if (!qrCode) return;

    try {
        // Get blob from QR code
        const blob = await qrCode.getRawData('png');

        // Create form data
        const formData = new FormData();
        formData.append('image', blob, `${code}.png`);
        formData.append('code', code);

        // Upload to server
        await fetch('save-image.php', {
            method: 'POST',
            body: formData
        });
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

    // Update QR code via API
    const result = await apiCall('update', formData);

    if (result.success) {
        // Save updated QR code image
        await saveQrImage(code);

        // Show success modal
        showModal('successModal');
        showToast(result.message, 'success');
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
        btn.addEventListener('click', () => {
            const url = btn.dataset.url;
            copyToClipboard(url);
        });
    });

    // Initialize search functionality
    initSearch();

    // Initialize sorting functionality
    initSorting();
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
