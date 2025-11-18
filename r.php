<?php
/**
 * Redirect Handler
 *
 * Public-facing redirect handler for QR codes
 * Handles URL pattern: /r.php?c=ABC123 or clean URL /ABC123
 *
 * Flow:
 * 1. Extract code from query parameter
 * 2. Look up destination URL in database
 * 3. Increment click counter
 * 4. Redirect to destination (301 permanent redirect)
 * 5. Show friendly 404 if code not found
 */

require_once __DIR__ . '/includes/init.php';

// Get code from query parameter
$code = $_GET['c'] ?? '';

// Validate code format (alphanumeric, 6-10 characters)
if (empty($code) || !preg_match('/^[A-Za-z0-9]{6,10}$/', $code)) {
    show404Page('Invalid QR code format');
    exit;
}

// Sanitize code
$code = strtoupper(trim($code));

try {
    // Look up QR code in database
    $qrCode = $db->fetchOne(
        "SELECT id, destination_url FROM qr_codes WHERE code = ? LIMIT 1",
        "s",
        [$code]
    );

    if (!$qrCode) {
        // Code not found
        show404Page('QR code not found');
        exit;
    }

    // Increment click counter (do this before redirect for better reliability)
    $db->execute(
        "UPDATE qr_codes SET click_count = click_count + 1 WHERE id = ?",
        "i",
        [$qrCode['id']]
    );

    // Log the redirect
    logError("QR redirect: Code={$code}, ID={$qrCode['id']}, URL={$qrCode['destination_url']}", 'INFO');

    // Perform 301 permanent redirect
    redirect($qrCode['destination_url'], 301);

} catch (Exception $e) {
    // Log error and show friendly error page
    logError("Redirect error: " . $e->getMessage());
    show404Page('An error occurred while processing your request');
}

/**
 * Display friendly 404 page
 *
 * @param string $message Error message to display
 */
function show404Page($message = 'QR code not found') {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>QR Code Not Found</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .container {
                background: white;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                padding: 60px 40px;
                text-align: center;
                max-width: 500px;
                width: 100%;
            }

            .error-code {
                font-size: 120px;
                font-weight: bold;
                color: #667eea;
                line-height: 1;
                margin-bottom: 20px;
            }

            h1 {
                font-size: 32px;
                color: #333;
                margin-bottom: 15px;
            }

            p {
                font-size: 18px;
                color: #666;
                margin-bottom: 30px;
                line-height: 1.6;
            }

            .btn {
                display: inline-block;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                padding: 15px 40px;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                transition: transform 0.2s, box-shadow 0.2s;
            }

            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            }

            @media (max-width: 600px) {
                .container {
                    padding: 40px 20px;
                }

                .error-code {
                    font-size: 80px;
                }

                h1 {
                    font-size: 24px;
                }

                p {
                    font-size: 16px;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="error-code">404</div>
            <h1>QR Code Not Found</h1>
            <p><?php echo htmlspecialchars($message); ?></p>
            <p>The QR code you're looking for doesn't exist or may have been removed.</p>
            <a href="<?php echo BASE_URL; ?>" class="btn">Go to Homepage</a>
        </div>
    </body>
    </html>
    <?php
}
?>
