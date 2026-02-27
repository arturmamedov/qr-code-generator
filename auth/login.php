<?php
/**
 * Login Page — Supabase Auth
 *
 * Provides email/password, Google OAuth, and magic link login.
 * Only used when Supabase auth is configured.
 * If Supabase is not configured, redirects to main page (.htaccess handles auth).
 */

require_once __DIR__ . '/../includes/init.php';

// If Supabase auth is not enabled, redirect to dashboard (htaccess handles auth)
if (!AuthMiddleware::isEnabled()) {
    header('Location: /index.php');
    exit;
}

// If already logged in, redirect to intended page or dashboard
if (AuthMiddleware::currentUser() !== null) {
    $redirect = $_GET['redirect'] ?? '/index.php';
    header('Location: ' . $redirect);
    exit;
}

$redirect = htmlspecialchars($_GET['redirect'] ?? '/index.php', ENT_QUOTES, 'UTF-8');
$pageTitle = 'Sign In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - QR Code Manager</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='%23667eea'/><rect x='10' y='10' width='30' height='30' fill='white'/><rect x='60' y='10' width='30' height='30' fill='white'/><rect x='10' y='60' width='30' height='30' fill='white'/></svg>">
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: var(--gray-50);
        }
        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
        }
        .login-card h1 {
            font-family: var(--font-family-headings);
            font-size: 1.5rem;
            text-align: center;
            margin-bottom: 0.5rem;
            color: var(--gray-900);
        }
        .login-card .subtitle {
            text-align: center;
            color: var(--gray-500);
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }
        .login-form .form-group {
            margin-bottom: 1rem;
        }
        .login-form label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.35rem;
            font-size: 0.875rem;
            color: var(--gray-700);
        }
        .login-form input[type="email"],
        .login-form input[type="password"] {
            width: 100%;
            padding: 0.65rem 0.85rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: var(--font-family-body);
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .login-form input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(83, 206, 209, 0.15);
        }
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: var(--font-family-body);
            transition: background 0.2s, transform 0.1s;
        }
        .btn-login:active {
            transform: scale(0.98);
        }
        .btn-login-primary {
            background: var(--primary-color);
            color: white;
        }
        .btn-login-primary:hover {
            background: var(--primary-dark);
        }
        .btn-login-google {
            background: white;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-login-google:hover {
            background: var(--gray-50);
        }
        .btn-login-magic {
            background: transparent;
            color: var(--primary-dark);
            border: 1px solid var(--primary-color);
        }
        .btn-login-magic:hover {
            background: rgba(83, 206, 209, 0.08);
        }
        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: var(--gray-400);
            font-size: 0.85rem;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--gray-200);
        }
        .divider span {
            padding: 0 0.75rem;
        }
        .login-error {
            background: #fef2f2;
            color: var(--danger-color);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            display: none;
        }
        .login-success {
            background: #f0fdf4;
            color: #166534;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            display: none;
        }
        .login-tabs {
            display: flex;
            border-bottom: 2px solid var(--gray-200);
            margin-bottom: 1.5rem;
        }
        .login-tab {
            flex: 1;
            padding: 0.75rem;
            text-align: center;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--gray-500);
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: color 0.2s, border-color 0.2s;
            background: none;
            border-top: none;
            border-left: none;
            border-right: none;
            font-family: var(--font-family-body);
        }
        .login-tab.active {
            color: var(--primary-dark);
            border-bottom-color: var(--primary-color);
        }
        .login-tab:hover {
            color: var(--primary-dark);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
    <script>
        window.__SUPABASE_URL__      = '<?php echo htmlspecialchars(SUPABASE_URL, ENT_QUOTES, "UTF-8"); ?>';
        window.__SUPABASE_ANON_KEY__ = '<?php echo htmlspecialchars(SUPABASE_ANON_KEY, ENT_QUOTES, "UTF-8"); ?>';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <h1>QR Code Manager</h1>
            <p class="subtitle">Sign in to manage your QR codes</p>

            <div id="loginError" class="login-error"></div>
            <div id="loginSuccess" class="login-success"></div>

            <!-- Tabs -->
            <div class="login-tabs">
                <button class="login-tab active" data-tab="password">Email & Password</button>
                <button class="login-tab" data-tab="magic">Magic Link</button>
            </div>

            <!-- Email/Password Tab -->
            <div id="tab-password" class="tab-content active">
                <form id="loginForm" class="login-form">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required placeholder="you@example.com" autocomplete="email">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required placeholder="Your password" autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn-login btn-login-primary" id="btnLogin">Sign In</button>
                </form>
            </div>

            <!-- Magic Link Tab -->
            <div id="tab-magic" class="tab-content">
                <form id="magicLinkForm" class="login-form">
                    <div class="form-group">
                        <label for="magicEmail">Email</label>
                        <input type="email" id="magicEmail" name="email" required placeholder="you@example.com" autocomplete="email">
                    </div>
                    <button type="submit" class="btn-login btn-login-magic" id="btnMagicLink">Send Magic Link</button>
                </form>
            </div>

            <div class="divider"><span>or</span></div>

            <!-- Google OAuth -->
            <button type="button" class="btn-login btn-login-google" id="btnGoogle">
                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" fill="#4285F4"/>
                    <path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 009 18z" fill="#34A853"/>
                    <path d="M3.964 10.71A5.41 5.41 0 013.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 000 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
                    <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 00.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z" fill="#EA4335"/>
                </svg>
                Sign in with Google
            </button>
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

        var errorEl   = document.getElementById('loginError');
        var successEl = document.getElementById('loginSuccess');

        function showError(msg) {
            errorEl.textContent = msg;
            errorEl.style.display = 'block';
            successEl.style.display = 'none';
        }

        function showSuccess(msg) {
            successEl.textContent = msg;
            successEl.style.display = 'block';
            errorEl.style.display = 'none';
        }

        function syncCookieAndRedirect(token) {
            document.cookie = 'sb-access-token=' + token + '; path=/; SameSite=Lax; Secure';
            window.location.href = REDIRECT_URL;
        }

        // ---------------------------------------------------------------
        // Handle #access_token= in URL hash (implicit flow fallback).
        //
        // This happens when Supabase cannot use the PKCE emailRedirectTo
        // URL because it isn't in the Supabase Redirect URL allow-list, so
        // it falls back to sending the token directly in the hash fragment.
        // Fix the root cause by adding /auth/callback.php to the Supabase
        // Redirect URLs list — but this handler makes login.php resilient
        // in the meantime.
        // ---------------------------------------------------------------
        var rawHash = window.location.hash;
        if (rawHash && rawHash.indexOf('access_token=') !== -1) {
            var hashParams = new URLSearchParams(rawHash.startsWith('#') ? rawHash.slice(1) : rawHash);
            var hashAccessToken  = hashParams.get('access_token');
            var hashRefreshToken = hashParams.get('refresh_token') || '';

            if (hashAccessToken) {
                // Replace card with a loading indicator while we exchange the token
                var card = document.querySelector('.login-card');
                card.innerHTML =
                    '<h1 style="font-family:var(--font-family-headings);font-size:1.5rem;text-align:center;margin-bottom:0.5rem">QR Code Manager</h1>' +
                    '<p style="text-align:center;color:var(--gray-500);margin-bottom:1.5rem">Signing you in\u2026</p>' +
                    '<div id="loginError" class="login-error" style="display:none"></div>';
                errorEl = document.getElementById('loginError');

                sb.auth.setSession({ access_token: hashAccessToken, refresh_token: hashRefreshToken })
                    .then(function(result) {
                        if (result.error || !result.data || !result.data.session) {
                            var msg = (result.error && result.error.message) || 'Link has expired or is invalid.';
                            card.innerHTML =
                                '<h1 style="font-family:var(--font-family-headings);font-size:1.5rem;text-align:center;margin-bottom:0.5rem">Link Expired</h1>' +
                                '<p style="text-align:center;color:var(--gray-500);margin-bottom:1rem">' + msg + '</p>' +
                                '<p style="text-align:center"><a href="/auth/login.php" style="color:var(--primary-dark)">Request a new link</a></p>';
                            return;
                        }
                        // setSession may return a refreshed token — always use the returned one
                        syncCookieAndRedirect(result.data.session.access_token);
                    })
                    .catch(function() {
                        card.innerHTML =
                            '<h1 style="font-family:var(--font-family-headings);font-size:1.5rem;text-align:center;margin-bottom:0.5rem">Sign-in Failed</h1>' +
                            '<p style="text-align:center;color:var(--gray-500);margin-bottom:1rem">Please try again.</p>' +
                            '<p style="text-align:center"><a href="/auth/login.php" style="color:var(--primary-dark)">Back to sign in</a></p>';
                    });

                return; // Skip normal login form setup while hash is being processed
            }
        }

        // ---------------------------------------------------------------
        // Normal flow: check for existing session or PKCE code in URL
        // ---------------------------------------------------------------

        // onAuthStateChange catches PKCE code exchange completion and token refreshes
        sb.auth.onAuthStateChange(function(event, session) {
            if (event === 'SIGNED_IN' && session && session.access_token) {
                syncCookieAndRedirect(session.access_token);
            }
        });

        // getSession handles existing sessions stored in localStorage
        sb.auth.getSession().then(function(result) {
            var session = result.data && result.data.session;
            if (session && session.access_token) {
                syncCookieAndRedirect(session.access_token);
            }
        });

        // Tab switching
        document.querySelectorAll('.login-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.login-tab').forEach(function(t) { t.classList.remove('active'); });
                document.querySelectorAll('.tab-content').forEach(function(c) { c.classList.remove('active'); });
                tab.classList.add('active');
                document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
                errorEl.style.display = 'none';
                successEl.style.display = 'none';
            });
        });

        // Email/Password login
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            var email    = document.getElementById('email').value.trim();
            var password = document.getElementById('password').value;
            var btn      = document.getElementById('btnLogin');

            btn.disabled = true;
            btn.textContent = 'Signing in...';
            errorEl.style.display = 'none';

            sb.auth.signInWithPassword({ email: email, password: password })
                .then(function(result) {
                    if (result.error) {
                        showError(result.error.message);
                        btn.disabled = false;
                        btn.textContent = 'Sign In';
                        return;
                    }
                    syncCookieAndRedirect(result.data.session.access_token);
                })
                .catch(function(err) {
                    showError(err.message || 'Login failed');
                    btn.disabled = false;
                    btn.textContent = 'Sign In';
                });
        });

        // Magic Link login
        document.getElementById('magicLinkForm').addEventListener('submit', function(e) {
            e.preventDefault();
            var email = document.getElementById('magicEmail').value.trim();
            var btn   = document.getElementById('btnMagicLink');

            btn.disabled = true;
            btn.textContent = 'Sending...';
            errorEl.style.display = 'none';

            sb.auth.signInWithOtp({
                email: email,
                options: {
                    emailRedirectTo: window.location.origin + '/auth/callback.php?redirect=' + encodeURIComponent(REDIRECT_URL)
                }
            }).then(function(result) {
                if (result.error) {
                    showError(result.error.message);
                    btn.disabled = false;
                    btn.textContent = 'Send Magic Link';
                    return;
                }
                showSuccess('Check your email for the magic link!');
                btn.disabled = false;
                btn.textContent = 'Send Magic Link';
            }).catch(function(err) {
                showError(err.message || 'Failed to send magic link');
                btn.disabled = false;
                btn.textContent = 'Send Magic Link';
            });
        });

        // Google OAuth
        document.getElementById('btnGoogle').addEventListener('click', function() {
            sb.auth.signInWithOAuth({
                provider: 'google',
                options: {
                    redirectTo: window.location.origin + '/auth/callback.php?redirect=' + encodeURIComponent(REDIRECT_URL)
                }
            }).then(function(result) {
                if (result.error) {
                    showError(result.error.message);
                }
            });
        });
    })();
    </script>
</body>
</html>
