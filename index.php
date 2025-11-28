<?php
/**
 * Admin Dashboard
 *
 * Main admin page showing all QR codes in a table with stats
 * Protected by HTTP Basic Auth via .htaccess
 */

require_once __DIR__ . '/includes/init.php';

// Fetch all QR codes with favorite version info
$qrCodes = $db->fetchAll(
    "SELECT id, code, title, description, destination_url, click_count, tags,
            favorite_version_id, created_at, updated_at
     FROM qr_codes
     ORDER BY created_at DESC"
);

// Enhance QR codes with version information
foreach ($qrCodes as &$qr) {
    // Get version count
    $qr['version_count'] = getVersionCount($qr['id']);

    // Get favorite version details
    $favoriteVersion = getFavoriteVersion($qr['id']);
    if ($favoriteVersion) {
        $qr['favorite_version'] = $favoriteVersion;
        $qr['image_url'] = $favoriteVersion['image_url'];
    } else {
        // Fallback for backward compatibility (old QR codes without versions)
        $qr['image_url'] = BASE_URL . '/generated/' . $qr['code'] . '.png';
        $qr['favorite_version'] = null;
    }
}
unset($qr); // Break reference

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
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='%23667eea'/><rect x='10' y='10' width='30' height='30' fill='white'/><rect x='60' y='10' width='30' height='30' fill='white'/><rect x='10' y='60' width='30' height='30' fill='white'/></svg>">
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

            <?php if (!empty($qrCodes)):
                // Calculate average clicks
                $avgClicks = $totalQrCodes > 0 ? round($totalClicks / $totalQrCodes, 1) : 0;
            ?>
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($avgClicks, 1); ?></div>
                    <div class="stat-label">Average Clicks</div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Top Performers Widget -->
        <?php if (!empty($qrCodes)):
            // Get top 5 QR codes by clicks
            $topQrCodes = $qrCodes;
            usort($topQrCodes, function($a, $b) {
                return $b['click_count'] - $a['click_count'];
            });
            $topQrCodes = array_slice($topQrCodes, 0, 5);

            // Only show if at least one has clicks
            $hasClicks = false;
            foreach ($topQrCodes as $qr) {
                if ($qr['click_count'] > 0) {
                    $hasClicks = true;
                    break;
                }
            }

            if ($hasClicks):
        ?>
        <div class="top-performers-widget">
            <h3>🏆 Top Performers</h3>
            <div class="top-performers-list">
                <?php foreach ($topQrCodes as $index => $qr):
                    if ($qr['click_count'] > 0):
                ?>
                    <div class="top-performer-item">
                        <div class="performer-rank"><?php echo $index + 1; ?></div>
                        <div class="performer-info">
                            <div class="performer-title"><?php echo htmlspecialchars($qr['title']); ?></div>
                            <div class="performer-code"><?php echo $qr['code']; ?></div>
                        </div>
                        <div class="performer-clicks">
                            <div class="clicks-value"><?php echo number_format($qr['click_count']); ?></div>
                            <div class="clicks-label">clicks</div>
                        </div>
                    </div>
                <?php
                    endif;
                endforeach; ?>
            </div>
        </div>
        <?php
            endif;
        endif; ?>

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
                            <th class="sortable" data-sort="title">
                                Title <span class="sort-indicator"></span>
                            </th>
                            <th class="sortable" data-sort="code">
                                Code <span class="sort-indicator"></span>
                            </th>
                            <th>Destination</th>
                            <th class="sortable" data-sort="clicks">
                                Clicks <span class="sort-indicator"></span>
                            </th>
                            <th class="sortable" data-sort="created">
                                Created <span class="sort-indicator"></span>
                            </th>
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
                                data-created="<?php echo $qr['created_at']; ?>"
                                data-favorite-version-id="<?php echo isset($qr['favorite_version']['id']) ? $qr['favorite_version']['id'] : ''; ?>">
                                <!-- Preview -->
                                <td class="preview-cell">
                                    <?php
                                    // Check if favorite version exists
                                    if ($qr['favorite_version']):
                                        $imagePath = getVersionImagePath($qr['id'], $qr['favorite_version']['id']);
                                        $imageDownloadUrl = "generated/qr-code-{$qr['id']}/v{$qr['favorite_version']['id']}.png";
                                        if (file_exists($imagePath)):
                                    ?>
                                        <img src="generated/qr-code-<?php echo $qr['id']; ?>/v<?php echo $qr['favorite_version']['id']; ?>.png"
                                             alt="QR Code"
                                             class="qr-preview"
                                             title="<?php echo htmlspecialchars($qr['favorite_version']['version_name']); ?> (Click to view full size)">
                                    <?php
                                        else:
                                    ?>
                                        <div class="qr-placeholder">No Image</div>
                                    <?php
                                        endif;
                                    else:
                                        // Fallback for old QR codes without versions
                                        $imagePath = GENERATED_PATH . '/' . $qr['code'] . '.png';
                                        $imageDownloadUrl = "generated/{$qr['code']}.png";
                                        if (file_exists($imagePath)):
                                    ?>
                                        <img src="generated/<?php echo $qr['code']; ?>.png"
                                             alt="QR Code"
                                             class="qr-preview"
                                             title="Click to view full size">
                                    <?php else: ?>
                                        <div class="qr-placeholder">No Image</div>
                                    <?php
                                        endif;
                                    endif;
                                    ?>
                                </td>

                                <!-- Title -->
                                <td>
                                    <div class="title-cell">
                                        <strong><?php echo htmlspecialchars($qr['title']); ?></strong>
                                        <?php if ($qr['version_count'] > 0): ?>
                                            <span class="version-count-badge" title="<?php echo $qr['version_count']; ?> version<?php echo $qr['version_count'] != 1 ? 's' : ''; ?>">
                                                <?php echo $qr['version_count']; ?> version<?php echo $qr['version_count'] != 1 ? 's' : ''; ?>
                                            </span>
                                        <?php endif; ?>
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
                                            <a href="<?php echo $imageDownloadUrl; ?>"
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

                <!-- Pagination Controls -->
                <div class="pagination-container">
                    <div class="pagination-info">
                        <span id="paginationInfo">Showing 1-25 of <?php echo $totalQrCodes; ?></span>
                    </div>
                    <div class="pagination-controls">
                        <button id="prevPage" class="btn-page" disabled>
                            ← Previous
                        </button>
                        <div id="pageNumbers" class="page-numbers"></div>
                        <button id="nextPage" class="btn-page">
                            Next →
                        </button>
                    </div>
                    <div class="pagination-settings">
                        <label for="pageSize">Per page:</label>
                        <select id="pageSize" class="page-size-select">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- QR Preview Modal -->
    <div id="qrPreviewModal" class="modal">
        <div class="modal-content qr-preview-modal">
            <button class="modal-close" id="closePreviewModal">✕</button>

            <div class="qr-preview-layout">
                <div class="qr-preview-image">
                    <img id="previewModalImage" src="" alt="QR Code" onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 fill=%22%23f0f0f0%22/><text x=%2250%22 y=%2250%22 text-anchor=%22middle%22 dy=%22.3em%22 font-family=%22Arial%22 font-size=%2212%22 fill=%22%23999%22>No Image</text></svg>';">
                </div>

                <div class="qr-preview-details">
                    <h2 id="previewModalTitle"></h2>

                    <div class="detail-group">
                        <label>Short Code:</label>
                        <div class="detail-value">
                            <code id="previewModalCode" class="qr-code-large"></code>
                            <button class="btn-copy-inline" id="previewCopyCode" title="Copy code">📋</button>
                        </div>
                    </div>

                    <div class="detail-group">
                        <label>Short URL:</label>
                        <div class="detail-value">
                            <code id="previewModalUrl"></code>
                            <button class="btn-copy-inline" id="previewCopyUrl" title="Copy URL">📋</button>
                        </div>
                    </div>

                    <div class="detail-group">
                        <label>Destination:</label>
                        <div class="detail-value">
                            <a id="previewModalDestination" href="" target="_blank" rel="noopener noreferrer"></a>
                        </div>
                    </div>

                    <div class="detail-group" id="previewDescriptionGroup" style="display: none;">
                        <label>Description:</label>
                        <div class="detail-value" id="previewModalDescription"></div>
                    </div>

                    <div class="detail-group" id="previewTagsGroup" style="display: none;">
                        <label>Tags:</label>
                        <div class="detail-value">
                            <div id="previewModalTags" class="tags-list"></div>
                        </div>
                    </div>

                    <div class="detail-stats">
                        <div class="stat-box">
                            <div class="stat-box-value" id="previewModalClicks">0</div>
                            <div class="stat-box-label">Clicks</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-box-value" id="previewModalCreated"></div>
                            <div class="stat-box-label">Created</div>
                        </div>
                    </div>

                    <div class="preview-modal-actions">
                        <a id="previewEditBtn" href="" class="btn btn-secondary">
                            ✏️ Edit
                        </a>
                        <a id="previewDownloadBtn" href="" download class="btn btn-primary">
                            ⬇️ Download
                        </a>
                        <button id="previewDeleteBtn" class="btn btn-danger">
                            🗑️ Delete
                        </button>
                    </div>
                </div>
            </div>
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
