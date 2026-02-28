<?php
/**
 * Account Settings Page — Supabase Auth
 *
 * Allows users to update their profile name, email, and password.
 * Uses sb.auth.getUser() to load current data and sb.auth.updateUser() to save.
 * Only used when Supabase auth is configured.
 */

require_once __DIR__ . '/../includes/init.php';

// Require Supabase auth
if (!AuthMiddleware::isEnabled()) {
    header('Location: /index.php');
    exit;
}

// Require authentication
$claims = AuthMiddleware::requireAuth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - QR Code Manager</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='%23667eea'/><rect x='10' y='10' width='30' height='30' fill='white'/><rect x='60' y='10' width='30' height='30' fill='white'/><rect x='10' y='60' width='30' height='30' fill='white'/></svg>">
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        .settings-wrapper {
            min-height: 100vh;
            padding: 2rem;
            background: var(--gray-50);
        }
        .settings-container {
            max-width: 520px;
            margin: 0 auto;
        }
        .settings-header {
            margin-bottom: 1.5rem;
        }
        .settings-header a {
            color: var(--primary-dark);
            text-decoration: none;
            font-size: 0.875rem;
        }
        .settings-header h1 {
            font-family: var(--font-family-headings);
            font-size: 1.5rem;
            margin: 0.5rem 0 0;
            color: var(--gray-900);
        }
        .settings-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 1.75rem;
            margin-bottom: 1.25rem;
        }
        .settings-card h2 {
            font-family: var(--font-family-headings);
            font-size: 1.1rem;
            margin: 0 0 1rem;
            color: var(--gray-900);
        }
        .settings-form .form-group {
            margin-bottom: 1rem;
        }
        .settings-form label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.35rem;
            font-size: 0.875rem;
            color: var(--gray-700);
        }
        .settings-form input[type="text"],
        .settings-form input[type="email"],
        .settings-form input[type="password"] {
            width: 100%;
            padding: 0.65rem 0.85rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: var(--font-family-body);
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .settings-form input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(83, 206, 209, 0.15);
        }
        .settings-form input:read-only {
            background: var(--gray-50);
            color: var(--gray-500);
            cursor: default;
        }
        .btn-settings {
            padding: 0.6rem 1.25rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            font-family: var(--font-family-body);
            transition: background 0.2s, transform 0.1s;
            background: var(--primary-color);
            color: white;
        }
        .btn-settings:hover { background: var(--primary-dark); }
        .btn-settings:active { transform: scale(0.98); }
        .btn-settings:disabled { opacity: 0.6; cursor: default; }
        .settings-msg {
            padding: 0.6rem 0.85rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            display: none;
        }
        .settings-msg.error {
            background: #fef2f2;
            color: var(--danger-color);
        }
        .settings-msg.success {
            background: #f0fdf4;
            color: #166534;
        }
        .email-hint {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-top: 0.25rem;
        }
    </style>
    <script>
        window.__SUPABASE_URL__      = '<?php echo htmlspecialchars(SUPABASE_URL, ENT_QUOTES, "UTF-8"); ?>';
        window.__SUPABASE_ANON_KEY__ = '<?php echo htmlspecialchars(SUPABASE_ANON_KEY, ENT_QUOTES, "UTF-8"); ?>';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
</head>
<body>
    <div class="settings-wrapper">
        <div class="settings-container">
            <div class="settings-header">
                <a href="/index.php">&larr; Back to Dashboard</a>
                <h1>Account Settings</h1>
            </div>

            <!-- Profile Section -->
            <div class="settings-card">
                <h2>Profile</h2>
                <div id="profileMsg" class="settings-msg"></div>
                <form id="profileForm" class="settings-form">
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" placeholder="Your name" autocomplete="name">
                    </div>
                    <button type="submit" class="btn-settings" id="btnProfile">Save Name</button>
                </form>
            </div>

            <!-- Email Section -->
            <div class="settings-card">
                <h2>Email</h2>
                <div id="emailMsg" class="settings-msg"></div>
                <form id="emailForm" class="settings-form">
                    <div class="form-group">
                        <label for="currentEmail">Current Email</label>
                        <input type="email" id="currentEmail" readonly>
                    </div>
                    <div class="form-group">
                        <label for="newEmail">New Email</label>
                        <input type="email" id="newEmail" required placeholder="new@example.com" autocomplete="email">
                    </div>
                    <p class="email-hint">A confirmation link will be sent to your new email address.</p>
                    <button type="submit" class="btn-settings" id="btnEmail">Update Email</button>
                </form>
            </div>

            <!-- Password Section -->
            <div class="settings-card">
                <h2>Password</h2>
                <div id="passwordMsg" class="settings-msg"></div>
                <form id="passwordForm" class="settings-form">
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <input type="password" id="newPassword" required placeholder="Minimum 6 characters" autocomplete="new-password" minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" required placeholder="Repeat your password" autocomplete="new-password" minlength="6">
                    </div>
                    <button type="submit" class="btn-settings" id="btnPassword">Update Password</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    (function() {
        'use strict';

        var sb = supabase.createClient(window.__SUPABASE_URL__, window.__SUPABASE_ANON_KEY__, {
            auth: {
                autoRefreshToken: true,
                persistSession: true,
                detectSessionInUrl: false
            }
        });

        // ---------------------------------------------------------------
        // Helpers
        // ---------------------------------------------------------------
        function showMsg(el, type, msg) {
            el.className = 'settings-msg ' + type;
            el.textContent = msg;
            el.style.display = 'block';
        }

        function clearMsg(el) {
            el.style.display = 'none';
        }

        function resetBtn(btn, text) {
            btn.disabled = false;
            btn.textContent = text;
        }

        // ---------------------------------------------------------------
        // Load current user data
        // ---------------------------------------------------------------
        sb.auth.getUser().then(function(result) {
            if (result.error || !result.data || !result.data.user) {
                window.location.href = '/auth/login.php';
                return;
            }
            var user = result.data.user;
            document.getElementById('currentEmail').value = user.email || '';
            document.getElementById('fullName').value = (user.user_metadata && user.user_metadata.full_name) || '';
        });

        // ---------------------------------------------------------------
        // Profile — update full name
        // ---------------------------------------------------------------
        var profileMsg = document.getElementById('profileMsg');

        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            var name = document.getElementById('fullName').value.trim();
            var btn  = document.getElementById('btnProfile');

            btn.disabled = true;
            btn.textContent = 'Saving...';
            clearMsg(profileMsg);

            sb.auth.updateUser({
                data: { full_name: name }
            }).then(function(result) {
                if (result.error) {
                    showMsg(profileMsg, 'error', result.error.message);
                    resetBtn(btn, 'Save Name');
                    return;
                }
                showMsg(profileMsg, 'success', 'Name updated!');
                resetBtn(btn, 'Save Name');
                // Sync new token to cookie
                if (result.data && result.data.user && result.data.session) {
                    document.cookie = 'sb-access-token=' + result.data.session.access_token + '; path=/; SameSite=Lax; Secure';
                }
            }).catch(function(err) {
                showMsg(profileMsg, 'error', err.message || 'Failed to update name.');
                resetBtn(btn, 'Save Name');
            });
        });

        // ---------------------------------------------------------------
        // Email — update email (sends confirmation)
        // ---------------------------------------------------------------
        var emailMsg = document.getElementById('emailMsg');

        document.getElementById('emailForm').addEventListener('submit', function(e) {
            e.preventDefault();
            var newEmail = document.getElementById('newEmail').value.trim();
            var btn      = document.getElementById('btnEmail');

            if (!newEmail) {
                showMsg(emailMsg, 'error', 'Please enter a new email address.');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Sending...';
            clearMsg(emailMsg);

            sb.auth.updateUser({
                email: newEmail
            }).then(function(result) {
                if (result.error) {
                    showMsg(emailMsg, 'error', result.error.message);
                    resetBtn(btn, 'Update Email');
                    return;
                }
                showMsg(emailMsg, 'success', 'Confirmation link sent to ' + newEmail + '. Check your inbox.');
                resetBtn(btn, 'Update Email');
                document.getElementById('newEmail').value = '';
            }).catch(function(err) {
                showMsg(emailMsg, 'error', err.message || 'Failed to update email.');
                resetBtn(btn, 'Update Email');
            });
        });

        // ---------------------------------------------------------------
        // Password — update password
        // ---------------------------------------------------------------
        var passwordMsg = document.getElementById('passwordMsg');

        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            var newPassword     = document.getElementById('newPassword').value;
            var confirmPassword = document.getElementById('confirmPassword').value;
            var btn             = document.getElementById('btnPassword');

            if (newPassword.length < 6) {
                showMsg(passwordMsg, 'error', 'Password must be at least 6 characters.');
                return;
            }
            if (newPassword !== confirmPassword) {
                showMsg(passwordMsg, 'error', 'Passwords do not match.');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Updating...';
            clearMsg(passwordMsg);

            sb.auth.updateUser({
                password: newPassword
            }).then(function(result) {
                if (result.error) {
                    showMsg(passwordMsg, 'error', result.error.message);
                    resetBtn(btn, 'Update Password');
                    return;
                }
                showMsg(passwordMsg, 'success', 'Password updated!');
                resetBtn(btn, 'Update Password');
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmPassword').value = '';
                // Sync new token to cookie
                if (result.data && result.data.session) {
                    document.cookie = 'sb-access-token=' + result.data.session.access_token + '; path=/; SameSite=Lax; Secure';
                }
            }).catch(function(err) {
                showMsg(passwordMsg, 'error', err.message || 'Failed to update password.');
                resetBtn(btn, 'Update Password');
            });
        });
    })();
    </script>
</body>
</html>
