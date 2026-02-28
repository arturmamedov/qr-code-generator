# Supabase Auth Integration Plan

> **Status:** Implemented
> **Implemented:** 2026-02-27
> **Commit:** `Feature: Supabase Auth integration with backward-compatible .htaccess fallback`

## The Problem

The app currently uses **HTTP Basic Auth via .htaccess** — a browser popup for username/password, credentials stored in `.htpasswd`. This works on shared hosting but:
- No user management UI (editing `.htpasswd` requires server access)
- No OAuth (Google login), no magic links
- No role-based access (all authenticated users are equal)
- No API for Coolify deployments where `.htpasswd` is harder to manage
- Can't add features like "remember me", password reset, or user invitations

Supabase Auth replaces this with a proper auth system while keeping the same simplicity.

---

## Key Differences From the Generic Guide

The generic guide assumes a typical setup. Here's what's different about this project and what changes:

| Generic Guide Says | This Project's Reality | Adaptation |
|---|---|---|
| Use `firebase/php-jwt` + `guzzlehttp/guzzle` + `symfony/cache` (composer) | **No composer, no vendor/, FTP deployment** | Pure PHP HS256 JWT verification (~40 lines, zero dependencies) |
| Shared header/footer for injecting scripts | **Each page renders its own complete HTML** | Create a small `includes/auth-head.php` partial for the `<head>` injections |
| Guard constant (`WEB_INIT`, `APP_INIT`) | **No guard constants exist** | Skip — `.htaccess` already blocks direct access to `includes/` |
| RLS policies + `user_id` column | **Single-tenant admin app** — all admins manage all QR codes | Skip RLS entirely. Auth = "are you logged in?" + "are you admin?" |
| JWKS + RS256 support | Adds 3 composer dependencies | JWKS with `openssl_verify()` (PHP built-in). RS256/ES256 + HS256 fallback. Zero dependencies. |
| `api-versions.php` and `save-image.php` currently unprotected | `.htaccess` only protects `index\|create\|edit\|api\.php` | Fix this — add auth to both endpoints |

### JWT Verification Strategy

Supabase migrated from HS256 (symmetric) to RS256/ES256 (asymmetric) JWT signing in mid-2025. New projects default to asymmetric keys. The app supports both:

1. **JWKS (RS256/ES256)** — Primary method. Public keys are fetched from Supabase's JWKS endpoint (`/auth/v1/.well-known/jwks.json`), cached to `data/jwks-cache.json` for 1 hour. Verified with PHP's built-in `openssl_verify()`.
2. **HS256** — Legacy fallback. Uses `SUPABASE_JWT_SECRET` with `hash_hmac()`. Only used if the JWT header specifies `alg: HS256`.

Zero external dependencies — both methods use PHP built-in functions.

---

## Architecture

### Auth Flow

```
Browser                         Server (PHP)                    Supabase
  │                                │                                │
  │  1. Load login page            │                                │
  │──────────────────────>         │                                │
  │  (auth/login.php)              │                                │
  │                                │                                │
  │  2. User enters credentials    │                                │
  │  (or clicks Google OAuth)      │                                │
  │  JS calls Supabase SDK ────────────────────────────────────────>│
  │                                │                                │
  │  3. Supabase returns JWT  <─────────────────────────────────────│
  │  (stored in localStorage)      │                                │
  │                                │                                │
  │  4. Navigate to admin page     │                                │
  │──────────────────────>         │                                │
  │  Cookie: sb-access-token=JWT   │                                │
  │  (or Authorization: Bearer)    │                                │
  │                                │                                │
  │                           5. PHP verifies JWT locally           │
  │                              (JWKS RS256/ES256 + HS256 fallback) │
  │                              Validates: sig, exp, aud, iss      │
  │                                │                                │
  │  6. Page rendered (or 401)     │                                │
  │<──────────────────────         │                                │
```

**Key point:** JWT verification is local. For JWKS (RS256/ES256), public keys are fetched once and cached for 1 hour. For legacy HS256, the JWT secret is a shared secret. No network call to Supabase per request.

### Token Delivery Strategy

**For page loads** (index.php, create.php, edit.php):
- Supabase JS SDK stores the token in a cookie (`sb-access-token`) after login
- PHP reads from cookie on page load

**For API calls** (api.php, api-versions.php, save-image.php):
- JavaScript sends `Authorization: Bearer <token>` header
- PHP reads from header

The AuthMiddleware checks both sources.

---

## Complete File Change List

| File | Change | Description |
|------|--------|-------------|
| `includes/auth.php` | **New** | ~160 lines. Pure PHP JWT verification, AuthMiddleware class |
| `includes/auth-head.php` | **New** | ~7 lines. `<head>` partial for Supabase JS injection |
| `assets/auth.js` | **New** | ~100 lines. Supabase client wrapper, cookie sync, Auth.fetch() |
| `auth/login.php` | **New** | Login page with email/password, Google OAuth, magic link, registration, forgot password |
| `auth/callback.php` | **New** | OAuth/magic link/recovery redirect handler with error forwarding |
| `auth/logout.php` | **New** | Sign out and redirect |
| `auth/settings.php` | **New** | Account settings — update name, email, password via `sb.auth.updateUser()` |
| `auth/reset-password.php` | **New** | Password reset form after recovery link |
| `config.example.php` | **Edit** | Add 6 Supabase constants |
| `.env.example` | **Edit** | Add 4 Supabase env vars |
| `includes/init.php` | **Edit** | Add 1 line: `require_once auth.php` |
| `index.php` | **Edit** | Add `requireAuth()` + auth-head include + Sign Out link |
| `create.php` | **Edit** | Add `requireAuth()` + auth-head include |
| `edit.php` | **Edit** | Add `requireAuth()` + auth-head include |
| `api.php` | **Edit** | Add `requireAuth()` |
| `api-versions.php` | **Edit** | Add `requireAuth()` |
| `save-image.php` | **Edit** | Add `requireAuth()` |
| `assets/app.js` | **Edit** | Add `getAuthHeaders()` helper + send auth header on all 9 fetch() calls |
| `.htaccess` | **Edit** | Comment out Basic Auth block, add auth/ access rules, protect auth-head.php |

| `assets/style.css` | **Edit** | Add user dropdown menu CSS (`.user-menu`, `.user-menu-dropdown`) |
| `index.php` | **Edit** | Replace Sign Out button with user dropdown menu (avatar, name, email, settings link, sign out) |

**Files that needed ZERO changes:** `r.php`, `Database.php`, `helpers.php`, `version-helpers.php`, `env-loader.php`, `database.sql`, `database-sqlite.sql`, `diagnostic.php`

---

## Backward Compatibility

| Deployment | Supabase configured? | What happens |
|---|---|---|
| Shared hosting (FTP) | No | `AuthMiddleware::isEnabled()` returns false. All auth methods are no-ops. `.htaccess` Basic Auth protects pages. **Zero behavior change.** |
| Shared hosting (FTP) | Yes | Supabase auth active. Remove Basic Auth from `.htaccess`. |
| Coolify | Yes | Supabase auth active. No `.htaccess` auth needed. |
| Coolify | No | No auth at all. Must configure either Supabase or `.htaccess`. |

---

## What the Generic Guide Gets Wrong for This Project

| Generic Guide Step | Problem | What We Do Instead |
|---|---|---|
| Step 1: Composer dependencies | No composer on shared hosting | Pure PHP HS256 verification. Zero dependencies. |
| Step 3: JWKS + CachedKeySet | Requires guzzle + symfony/cache | HS256 only. `hash_hmac()` is built into PHP. |
| Step 8: "Update shared header" | No shared header exists | Create `includes/auth-head.php` partial |
| Step 9: Guard constant | Project doesn't use guard constants | Skip. `.htaccess` blocks `includes/` directory. |
| Step 12: RLS policies | Single-tenant admin app | Skip entirely. Auth = login check only. |
| Token delivery | Only `Authorization` header | Cookie for page loads + header for API calls |
| `api-versions.php` protection | Not mentioned | Add `requireAuth()` — fixes existing security gap |

---

## Implementation Order (as executed)

1. Created `includes/auth.php` and `includes/auth-head.php`
2. Created `assets/auth.js`
3. Updated `config.example.php` and `.env.example` with Supabase constants
4. Updated `includes/init.php` to load auth.php
5. Created `auth/login.php`, `auth/callback.php`, `auth/logout.php`
6. Added `requireAuth()` to protected pages and API endpoints
7. Modified `assets/app.js` to send Bearer tokens via `getAuthHeaders()` helper
8. Updated `.htaccess` (commented out Basic Auth, added auth/ access rules)
9. Rewrote `includes/auth.php` for JWKS (RS256/ES256) with HS256 fallback
10. Fixed implicit flow handling (`#access_token=`) in `auth/login.php` and `auth/callback.php`
11. Created `auth/reset-password.php`, added forgot password + recovery routing + hash error display
12. Added email registration (sign-up) form to `auth/login.php`
13. Created `auth/settings.php` (account settings: name, email, password)
14. Added user dropdown menu to dashboard header (`index.php`, `assets/style.css`, `assets/app.js`)

---

**See also:** [Supabase Auth Configuration Guide](./supabase-auth-guide.md)
