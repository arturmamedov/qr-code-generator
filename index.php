<?php
/**
 * Admin Dashboard
 *
 * Main admin page showing all QR codes in a table with stats
 * Protected by HTTP Basic Auth via .htaccess
 */

require_once __DIR__ . '/includes/init.php';

// Fetch all QR codes
$qrCodes = $db->fetchAll(
    "SELECT id, code, title, description, destination_url, click_count, tags,
            created_at, updated_at
     FROM qr_codes
     ORDER BY created_at DESC"
);

// Calculate total stats
$totalQrCodes = count($qrCodes);
$totalClicks = array_sum(array_column($qrCodes, 'click_count'));

// Page title
$pageTitle = 'QR Code Manager - Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <h1>🎯 QR Code Manager</h1>
            <a href="create.php" class="btn btn-primary">
                <span>➕</span> Create New QR Code
            </a>
        </header>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $totalQrCodes; ?></div>
                    <div class="stat-label">Total QR Codes</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👆</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($totalClicks); ?></div>
                    <div class="stat-label">Total Clicks</div>
                </div>
            </div>
        </div>

        <!-- Search & Filter -->
        <?php if (!empty($qrCodes)): ?>
        <div class="search-section">
            <div class="search-container">
                <input type="text"
                       id="searchInput"
                       class="search-input"
                       placeholder="🔍 Search by title, code, destination, or tags...">
                <button id="clearSearch" class="btn-clear-search" style="display: none;">✕</button>
            </div>
            <div class="search-results-info">
                <span id="resultsCount">Showing all <?php echo $totalQrCodes; ?> QR codes</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- QR Codes Table -->
        <div class="table-container">
            <?php if (empty($qrCodes)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📱</div>
                    <h2>No QR Codes Yet</h2>
                    <p>Create your first QR code to get started!</p>
                    <a href="create.php" class="btn btn-primary">Create QR Code</a>
                </div>
            <?php else: ?>
                <table class="qr-table">
                    <thead>
                        <tr>
                            <th>Preview</th>
                            <th>Title</th>
                            <th>Code</th>
                            <th>Destination</th>
                            <th>Clicks</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="qrTableBody">
                        <?php foreach ($qrCodes as $qr): ?>
                            <tr data-id="<?php echo $qr['id']; ?>"
                                data-title="<?php echo htmlspecialchars(strtolower($qr['title'])); ?>"
                                data-code="<?php echo strtolower($qr['code']); ?>"
                                data-destination="<?php echo htmlspecialchars(strtolower($qr['destination_url'])); ?>"
                                data-tags="<?php echo htmlspecialchars(strtolower($qr['tags'])); ?>"
                                data-clicks="<?php echo $qr['click_count']; ?>"
                                data-created="<?php echo $qr['created_at']; ?>">
                                <!-- Preview -->
                                <td class="preview-cell">
                                    <?php
                                    $imagePath = GENERATED_PATH . '/' . $qr['code'] . '.png';
                                    if (file_exists($imagePath)):
                                    ?>
                                        <img src="generated/<?php echo $qr['code']; ?>.png"
                                             alt="QR Code"
                                             class="qr-preview"
                                             title="Click to view full size">
                                    <?php else: ?>
                                        <div class="qr-placeholder">No Image</div>
                                    <?php endif; ?>
                                </td>

                                <!-- Title -->
                                <td>
                                    <div class="title-cell">
                                        <strong><?php echo htmlspecialchars($qr['title']); ?></strong>
                                        <?php if ($qr['description']): ?>
                                            <small><?php echo htmlspecialchars($qr['description']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Code -->
                                <td>
                                    <div class="code-cell">
                                        <code class="qr-code"><?php echo $qr['code']; ?></code>
                                        <button class="btn-copy"
                                                data-url="<?php echo getQrUrl($qr['code']); ?>"
                                                title="Copy URL">
                                            📋
                                        </button>
                                    </div>
                                </td>

                                <!-- Destination URL -->
                                <td class="url-cell">
                                    <a href="<?php echo htmlspecialchars($qr['destination_url']); ?>"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="destination-url">
                                        <?php
                                        $url = $qr['destination_url'];
                                        echo htmlspecialchars(strlen($url) > 40 ? substr($url, 0, 40) . '...' : $url);
                                        ?>
                                    </a>
                                </td>

                                <!-- Clicks -->
                                <td class="clicks-cell">
                                    <span class="click-count"><?php echo number_format($qr['click_count']); ?></span>
                                </td>

                                <!-- Created Date -->
                                <td class="date-cell">
                                    <?php echo formatDate($qr['created_at'], 'M d, Y'); ?>
                                </td>

                                <!-- Actions -->
                                <td class="actions-cell">
                                    <div class="action-buttons">
                                        <a href="edit.php?id=<?php echo $qr['id']; ?>"
                                           class="btn-action btn-edit"
                                           title="Edit">
                                            ✏️
                                        </a>

                                        <?php if (file_exists($imagePath)): ?>
                                            <a href="generated/<?php echo $qr['code']; ?>.png"
                                               download="qr-<?php echo $qr['code']; ?>.png"
                                               class="btn-action btn-download"
                                               title="Download">
                                                ⬇️
                                            </a>
                                        <?php endif; ?>

                                        <button class="btn-action btn-delete"
                                                data-id="<?php echo $qr['id']; ?>"
                                                data-code="<?php echo $qr['code']; ?>"
                                                title="Delete">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <!-- No Results Row (hidden by default, shown by JS when search has no matches) -->
                        <tr id="noResultsRow" style="display: none;">
                            <td colspan="7" class="no-results-cell">
                                <div class="empty-state">
                                    <div class="empty-icon">🔍</div>
                                    <h3>No QR codes found</h3>
                                    <p>Try adjusting your search terms</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Delete QR Code</h3>
            <p>Are you sure you want to delete QR code <strong id="deleteCode"></strong>?</p>
            <p class="warning">This action cannot be undone!</p>
            <div class="modal-actions">
                <button id="cancelDelete" class="btn btn-secondary">Cancel</button>
                <button id="confirmDelete" class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <script src="assets/app.js"></script>
</body>
</html>
