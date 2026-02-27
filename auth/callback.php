<?php
/**
 * Auth Callback — Supabase OAuth / Magic Link handler
 *
 * This page handles redirects from Supabase after OAuth or magic link login.
 * The Supabase JS SDK detects the tokens in the URL fragment (#access_token=...)
 * and fires a SIGNED_IN event, which auth.js picks up to sync the cookie.
 */

require_once __DIR__ . '/../includes/init.php';

// If Supabase auth is not enabled, redirect to dashboard
if (!AuthMiddleware::isEnabled()) {
    header('Location: /index.php');
    exit;
}

$redirect = htmlspecialchars($_GET['redirect'] ?? '/index.php', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signing in... - QR Code Manager</title>
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        .callback-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: var(--gray-50);
        }
        .callback-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 3rem;
            text-align: center;
            max-width: 400px;
        }
        .callback-card h2 {
            font-family: var(--font-family-headings);
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }
        .callback-card p {
            color: var(--gray-500);
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--gray-200);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1.5rem;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    <script>
        window.__SUPABASE_URL__      = '<?php echo htmlspecialchars(SUPABASE_URL, ENT_QUOTES, "UTF-8"); ?>';
        window.__SUPABASE_ANON_KEY__ = '<?php echo htmlspecialchars(SUPABASE_ANON_KEY, ENT_QUOTES, "UTF-8"); ?>';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
</head>
<body>
    <div class="callback-wrapper">
        <div class="callback-card">
            <div class="spinner"></div>
            <h2>Signing you in...</h2>
            <p>Please wait while we verify your credentials.</p>
        </div>
    </div>

    <script>
    (function() {
        'use strict';

        var REDIRECT_URL = '<?php echo $redirect; ?>';
        var sb = supabase.createClient(window.__SUPABASE_URL__, window.__SUPABASE_ANON_KEY__, {
            auth: {
                flowType: 'pkce',
                autoRefreshToken: true,
                persistSession: true,
                detectSessionInUrl: true
            }
        });

        function syncAndRedirect(token) {
            document.cookie = 'sb-access-token=' + token + '; path=/; SameSite=Lax; Secure';
            window.location.href = REDIRECT_URL;
        }

        // Primary: SDK auto-detects PKCE ?code= or implicit #access_token= via detectSessionInUrl
        sb.auth.onAuthStateChange(function(event, session) {
            if ((event === 'SIGNED_IN' || event === 'TOKEN_REFRESHED') && session && session.access_token) {
                syncAndRedirect(session.access_token);
            }
        });

        // Fallback 1: existing session in localStorage (e.g., page reload)
        sb.auth.getSession().then(function(result) {
            var session = result.data && result.data.session;
            if (session && session.access_token) {
                syncAndRedirect(session.access_token);
            }
        });

        // Fallback 2: explicit hash parsing in case SDK doesn't auto-detect
        // (e.g., PKCE client receiving an implicit-flow #access_token= URL)
        var rawHash = window.location.hash;
        if (rawHash && rawHash.indexOf('access_token=') !== -1) {
            var hashParams = new URLSearchParams(rawHash.startsWith('#') ? rawHash.slice(1) : rawHash);
            var accessToken  = hashParams.get('access_token');
            var refreshToken = hashParams.get('refresh_token') || '';
            if (accessToken) {
                sb.auth.setSession({ access_token: accessToken, refresh_token: refreshToken })
                    .then(function(result) {
                        if (result.data && result.data.session) {
                            syncAndRedirect(result.data.session.access_token);
                        }
                    });
            }
        }

        // Timeout fallback — redirect to login if auth doesn't complete in 10 seconds
        setTimeout(function() {
            window.location.href = '/auth/login.php';
        }, 10000);
    })();
    </script>
</body>
</html>
