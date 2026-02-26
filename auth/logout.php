<?php
/**
 * Logout Page — Supabase Auth
 *
 * Signs out the user and clears the auth cookie.
 */

require_once __DIR__ . '/../includes/init.php';

// Clear the auth cookie server-side
if (isset($_COOKIE['sb-access-token'])) {
    setcookie('sb-access-token', '', time() - 3600, '/');
}

// If Supabase auth is not enabled, redirect to dashboard
if (!AuthMiddleware::isEnabled()) {
    header('Location: /index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signing out... - QR Code Manager</title>
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        .logout-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: var(--gray-50);
        }
        .logout-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 3rem;
            text-align: center;
            max-width: 400px;
        }
        .logout-card h2 {
            font-family: var(--font-family-headings);
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }
        .logout-card p {
            color: var(--gray-500);
        }
    </style>
    <script>
        window.__SUPABASE_URL__      = '<?php echo htmlspecialchars(SUPABASE_URL, ENT_QUOTES, "UTF-8"); ?>';
        window.__SUPABASE_ANON_KEY__ = '<?php echo htmlspecialchars(SUPABASE_ANON_KEY, ENT_QUOTES, "UTF-8"); ?>';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
</head>
<body>
    <div class="logout-wrapper">
        <div class="logout-card">
            <h2>Signing out...</h2>
            <p>You will be redirected shortly.</p>
        </div>
    </div>

    <script>
    (function() {
        'use strict';

        var sb = supabase.createClient(window.__SUPABASE_URL__, window.__SUPABASE_ANON_KEY__, {
            auth: { persistSession: true }
        });

        // Clear cookie
        document.cookie = 'sb-access-token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';

        // Sign out from Supabase
        sb.auth.signOut().then(function() {
            window.location.href = '/auth/login.php';
        }).catch(function() {
            window.location.href = '/auth/login.php';
        });
    })();
    </script>
</body>
</html>
