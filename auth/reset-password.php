<?php
/**
 * Reset Password Page — Supabase Auth
 *
 * Shown after a user clicks a password recovery link.
 * The recovery session is already in Supabase's localStorage
 * (set by callback.php or login.php before redirecting here).
 * Uses sb.auth.updateUser({ password }) to change the password.
 */

require_once __DIR__ . '/../includes/init.php';

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
    <title>Set New Password - QR Code Manager</title>
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
        .btn-login:active { transform: scale(0.98); }
        .btn-login-primary {
            background: var(--primary-color);
            color: white;
        }
        .btn-login-primary:hover { background: var(--primary-dark); }
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
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray-500);
            font-size: 0.875rem;
        }
        .back-link a { color: var(--primary-dark); }
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
            <h1>Set New Password</h1>
            <p class="subtitle">Enter your new password below</p>

            <div id="resetError" class="login-error"></div>
            <div id="resetSuccess" class="login-success"></div>

            <form id="resetForm" class="login-form">
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" required placeholder="Minimum 6 characters" autocomplete="new-password" minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" required placeholder="Repeat your password" autocomplete="new-password" minlength="6">
                </div>
                <button type="submit" class="btn-login btn-login-primary" id="btnReset">Update Password</button>
            </form>

            <p class="back-link"><a href="/auth/login.php">Back to sign in</a></p>
        </div>
    </div>

    <script>
    (function() {
        'use strict';

        var sb = supabase.createClient(window.__SUPABASE_URL__, window.__SUPABASE_ANON_KEY__, {
            auth: {
                autoRefreshToken: true,
                persistSession: true,
                detectSessionInUrl: true
            }
        });

        var errorEl   = document.getElementById('resetError');
        var successEl = document.getElementById('resetSuccess');
        var form      = document.getElementById('resetForm');

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

        // Verify there's an active recovery session — if not, redirect to login
        sb.auth.getSession().then(function(result) {
            var session = result.data && result.data.session;
            if (!session) {
                window.location.href = '/auth/login.php';
            }
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            var newPassword     = document.getElementById('newPassword').value;
            var confirmPassword = document.getElementById('confirmPassword').value;
            var btn             = document.getElementById('btnReset');

            // Client-side validation
            if (newPassword.length < 6) {
                showError('Password must be at least 6 characters.');
                return;
            }
            if (newPassword !== confirmPassword) {
                showError('Passwords do not match.');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Updating...';
            errorEl.style.display = 'none';

            sb.auth.updateUser({ password: newPassword })
                .then(function(result) {
                    if (result.error) {
                        showError(result.error.message);
                        btn.disabled = false;
                        btn.textContent = 'Update Password';
                        return;
                    }

                    // Password updated — sync cookie and redirect to dashboard
                    var token = result.data && result.data.user && result.data.session
                        ? result.data.session.access_token
                        : null;

                    showSuccess('Password updated! Redirecting to dashboard\u2026');
                    form.style.display = 'none';

                    if (token) {
                        document.cookie = 'sb-access-token=' + token + '; path=/; SameSite=Lax; Secure';
                    }

                    setTimeout(function() {
                        window.location.href = '/index.php';
                    }, 1500);
                })
                .catch(function(err) {
                    showError(err.message || 'Failed to update password.');
                    btn.disabled = false;
                    btn.textContent = 'Update Password';
                });
        });
    })();
    </script>
</body>
</html>
