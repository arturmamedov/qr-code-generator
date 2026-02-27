# Supabase Auth Configuration Guide

This guide explains how to set up Supabase Auth for the QR Code Manager. Supabase Auth replaces HTTP Basic Auth (`.htaccess`/`.htpasswd`) with a modern authentication system supporting email/password, Google OAuth, and magic links.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Supabase Project Setup](#supabase-project-setup)
4. [Application Configuration](#application-configuration)
5. [Authentication Methods](#authentication-methods)
6. [How It Works](#how-it-works)
7. [Deployment Scenarios](#deployment-scenarios)
8. [Managing Users](#managing-users)
9. [Switching Back to HTTP Basic Auth](#switching-back-to-http-basic-auth)
10. [Troubleshooting](#troubleshooting)

---

## Overview

The QR Code Manager supports **two authentication methods**:

| Method | Best For | Requires |
|--------|----------|----------|
| **HTTP Basic Auth** (.htaccess) | Shared hosting, FTP deployments | Apache, `.htpasswd` file |
| **Supabase Auth** (JWT) | Coolify, Docker, modern deployments | Supabase account (free tier works) |

**Key principle:** If `SUPABASE_URL` is empty, Supabase Auth is completely disabled and the app behaves exactly as before with `.htaccess` protection. No code changes needed — it's a config-only switch.

---

## Prerequisites

- A [Supabase](https://supabase.com) account (free tier is fine)
- Your QR Code Manager deployed and running
- Access to environment variables (`.env` file or Coolify dashboard)

---

## Supabase Project Setup

### Step 1: Create a Supabase Project

1. Go to [supabase.com/dashboard](https://supabase.com/dashboard)
2. Click **"New Project"**
3. Choose a name (e.g., `qr-code-manager`)
4. Set a **database password** (save it — you won't see it again)
5. Choose a region close to your server
6. Click **"Create new project"**
7. Wait for the project to finish provisioning (~2 minutes)

### Step 2: Get Your API Keys

Go to **Settings → API** in the Supabase dashboard. You need 4 values:

| Key | Where to find | What it's for |
|-----|---------------|---------------|
| **Project URL** | Settings → API → Project URL | `SUPABASE_URL` |
| **anon/public key** | Settings → API → Project API keys → `anon` `public` | `SUPABASE_ANON_KEY` |
| **service_role key** | Settings → API → Project API keys → `service_role` `secret` | `SUPABASE_SERVICE_ROLE_KEY` |
| **JWT Secret** | Settings → API → JWT Settings → JWT Secret | `SUPABASE_JWT_SECRET` |

> **Security:** The `service_role` key and JWT Secret are sensitive. Never expose them in client-side code or commit them to git.

### Step 3: Create Your First User

1. Go to **Authentication → Users** in the Supabase dashboard
2. Click **"Add user"** → **"Create new user"**
3. Enter email and password
4. Click **"Create user"**

This user can now log into your QR Code Manager.

### Step 4: Configure Auth Providers (Optional)

#### Email/Password (enabled by default)
Already enabled. No extra config needed.

#### Google OAuth
1. Go to **Authentication → Providers** in Supabase dashboard
2. Find **Google** and toggle it on
3. You need a Google OAuth Client ID and Secret:
   - Go to [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
   - Create a new OAuth 2.0 Client ID
   - Set **Authorized redirect URI** to: `https://<your-supabase-project>.supabase.co/auth/v1/callback`
4. Enter the Client ID and Client Secret in Supabase
5. Click **Save**

#### Magic Links (enabled by default)
Already enabled via email OTP. Users enter their email, receive a link, click it, and they're logged in.

> **Note:** Supabase free tier includes 4 emails/hour for magic links. For production, configure a custom SMTP provider in **Settings → Auth → SMTP Settings**.

---

## Application Configuration

### Option A: Using `.env` File (Local Development)

Create a `.env` file from the template:

```bash
cp .env.example .env
```

Add the Supabase values:

```env
# Supabase Auth
SUPABASE_URL=https://your-project-id.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
SUPABASE_JWT_SECRET=your-jwt-secret-from-dashboard
SUPABASE_SERVICE_ROLE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Option B: Using Coolify Environment Variables

In your Coolify dashboard:

1. Go to your application → **Environment Variables**
2. Add each variable:
   - `SUPABASE_URL` = `https://your-project-id.supabase.co`
   - `SUPABASE_ANON_KEY` = `eyJ...`
   - `SUPABASE_JWT_SECRET` = `your-jwt-secret`
   - `SUPABASE_SERVICE_ROLE_KEY` = `eyJ...`
3. Redeploy the application

### Option C: Using `config.php` (Shared Hosting)

Edit your `config.php` and set the values directly:

```php
define('SUPABASE_URL', 'https://your-project-id.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJ...');
define('SUPABASE_JWT_SECRET', 'your-jwt-secret');
define('SUPABASE_SERVICE_ROLE_KEY', 'eyJ...');
```

### Disabling Supabase Auth

To use HTTP Basic Auth instead, simply leave `SUPABASE_URL` empty:

```env
SUPABASE_URL=
```

Or in `config.php`:
```php
define('SUPABASE_URL', '');
```

When `SUPABASE_URL` is empty, `AuthMiddleware::isEnabled()` returns `false` and all auth checks become no-ops.

---

## Authentication Methods

### Email & Password
Standard login with email and password. Users must be created in the Supabase dashboard first.

### Google OAuth
One-click Google sign-in. Requires Google OAuth setup in the Supabase dashboard (see Step 4 above).

### Magic Link
Passwordless login via email. The user enters their email, receives a link, clicks it, and is authenticated. Good for occasional admin access.

---

## How It Works

### Architecture

```
Page Load:
  Browser → Cookie (sb-access-token) → PHP AuthMiddleware → verifyToken() → Allow/Deny

API Call:
  JavaScript → Authorization: Bearer <token> → PHP AuthMiddleware → verifyToken() → Allow/Deny
```

### Token Flow

1. **Login**: User signs in via `auth/login.php`. Supabase JS SDK handles the auth flow and stores the session in `localStorage`.

2. **Cookie Sync**: `assets/auth.js` listens for auth state changes and syncs the access token to a cookie (`sb-access-token`). This cookie is sent with every page request.

3. **PHP Verification**: `includes/auth.php` (AuthMiddleware class) extracts the JWT from either:
   - `Authorization: Bearer <token>` header (API calls)
   - `sb-access-token` cookie (page loads)
   Then verifies the HS256 signature using `hash_hmac()` with `SUPABASE_JWT_SECRET`.

4. **Token Refresh**: The Supabase JS SDK automatically refreshes the token before it expires. When refreshed, `auth.js` updates the cookie.

5. **Logout**: `auth/logout.php` clears the cookie and calls `supabase.auth.signOut()` to destroy the session.

### Key Files

| File | Purpose |
|------|---------|
| `includes/auth.php` | AuthMiddleware class — pure PHP JWT verification |
| `includes/auth-head.php` | `<head>` partial — injects Supabase JS SDK when enabled |
| `assets/auth.js` | Client-side — session management, cookie sync, `Auth.fetch()` |
| `auth/login.php` | Login page with email/password, Google, magic link tabs |
| `auth/callback.php` | OAuth/magic link redirect handler |
| `auth/logout.php` | Sign out page |

### Zero Dependencies

The PHP-side JWT verification uses only built-in PHP functions:
- `hash_hmac('sha256', ...)` — signature verification
- `base64_decode()` — JWT decoding
- `json_decode()` — payload parsing

No composer packages, no external libraries.

---

## Deployment Scenarios

### Scenario 1: Shared Hosting (HTTP Basic Auth)

Leave `SUPABASE_URL` empty. Uncomment the Basic Auth block in `.htaccess`:

```apache
<FilesMatch "^(index|create|edit|api|api-versions|save-image)\.php$">
    AuthType Basic
    AuthName "QR Code Manager - Admin Area"
    AuthUserFile /absolute/path/to/.htpasswd
    Require valid-user
</FilesMatch>
```

### Scenario 2: Coolify (Supabase Auth)

1. Set Supabase env vars in Coolify dashboard
2. Leave `.htaccess` Basic Auth block commented out
3. Deploy — Supabase Auth handles everything

### Scenario 3: Docker (Supabase Auth)

Pass env vars via `docker-compose.yml` or `Dockerfile`:

```yaml
environment:
  - SUPABASE_URL=https://your-project.supabase.co
  - SUPABASE_ANON_KEY=eyJ...
  - SUPABASE_JWT_SECRET=your-secret
  - SUPABASE_SERVICE_ROLE_KEY=eyJ...
```

### Scenario 4: Local Development

1. Copy `.env.example` to `.env`
2. Add Supabase values
3. Run `php -S localhost:8000`
4. Visit `http://localhost:8000/auth/login.php`

---

## Managing Users

### Adding Users

Users are managed in the **Supabase dashboard** → **Authentication** → **Users**:

1. Click **"Add user"** → **"Create new user"**
2. Enter email and password
3. Click **"Create user"**

### Removing Users

1. Go to **Authentication** → **Users**
2. Find the user
3. Click the three-dot menu → **Delete user**

### Inviting Users

You can invite users via email:
1. Click **"Add user"** → **"Send invitation"**
2. Enter their email
3. They'll receive an invite link

### Restricting Access

Since this is a single-tenant admin app, you typically want to restrict who can create accounts:

1. Go to **Authentication → Settings** in Supabase dashboard
2. Disable **"Enable sign-ups"** to prevent self-registration
3. Only admin-created users can log in

---

## Switching Back to HTTP Basic Auth

If you want to switch back to HTTP Basic Auth:

1. Set `SUPABASE_URL` to empty:
   ```env
   SUPABASE_URL=
   ```

2. Uncomment the Basic Auth block in `.htaccess`:
   ```apache
   <FilesMatch "^(index|create|edit|api|api-versions|save-image)\.php$">
       AuthType Basic
       AuthName "QR Code Manager - Admin Area"
       AuthUserFile /absolute/path/to/.htpasswd
       Require valid-user
   </FilesMatch>
   ```

3. Ensure `.htpasswd` exists with valid credentials

That's it. The app automatically falls back to `.htaccess` protection.

---

## Troubleshooting

### "Unauthorized" or redirect loop to login page

- **Check JWT Secret**: Make sure `SUPABASE_JWT_SECRET` in your config matches the one in Supabase dashboard → Settings → API → JWT Settings
- **Check clock skew**: JWT validation checks expiry time. If your server clock is significantly off, tokens will appear expired. Run `date` on your server to verify.
- **Check cookie domain**: If your app is on a subdomain (e.g., `qr.example.com`), ensure cookies aren't being set for the wrong domain.

### Login page shows but Google button doesn't work

- Verify Google OAuth is enabled in Supabase dashboard
- Check the redirect URI in Google Cloud Console matches your Supabase project URL
- Check browser console for errors

### Magic link emails not arriving

- Supabase free tier: 4 emails/hour limit
- Check spam folder
- For production, configure custom SMTP in Supabase → Settings → Auth → SMTP Settings

### "Mixed content" errors in browser console

- Your app must be served over HTTPS when using Supabase Auth
- The Supabase JS SDK is loaded from a CDN over HTTPS
- If your app is HTTP, the cookie won't be sent (due to `Secure` flag)

### API calls return 401 after some time

- The JWT has expired and wasn't refreshed
- Check that `auth.js` is loaded on the page (via `auth-head.php` include)
- The Supabase JS SDK handles automatic refresh, but only if it's loaded

### How to check if Supabase Auth is active

Open browser console and type:
```javascript
console.log(window.Auth ? 'Supabase Auth active' : 'HTTP Basic Auth');
console.log(window.Auth && window.Auth.getToken() ? 'Logged in' : 'Not logged in');
```

### Server-side check

```php
if (AuthMiddleware::isEnabled()) {
    echo "Supabase Auth is active";
} else {
    echo "Using .htaccess auth";
}
```

---

**Last Updated:** 2026-02-27
