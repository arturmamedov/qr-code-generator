<?php
/**
 * Supabase Auth Middleware
 *
 * Verifies JWTs locally using HS256 (PHP built-in hash_hmac).
 * No external dependencies required.
 *
 * If Supabase is not configured (SUPABASE_URL empty), auth is skipped
 * and .htaccess HTTP Basic Auth is assumed to be handling protection.
 */

class AuthMiddleware {
    private static ?object $cachedClaims = null;

    /**
     * Check if Supabase auth is enabled
     */
    public static function isEnabled(): bool {
        return defined('SUPABASE_URL') && SUPABASE_URL !== ''
            && defined('SUPABASE_JWT_SECRET') && SUPABASE_JWT_SECRET !== '';
    }

    /**
     * Require any authenticated user. Redirects to login or returns 401 if not.
     *
     * @return object|null JWT claims if Supabase auth is active, null if using .htaccess auth
     */
    public static function requireAuth(): ?object {
        if (!self::isEnabled()) {
            return null; // .htaccess handles auth
        }

        $claims = self::currentUser();
        if ($claims === null) {
            self::denyAccess();
        }
        return $claims;
    }

    /**
     * Require a specific role (checks app_metadata.role).
     *
     * @param string $role Required role name
     * @return object|null JWT claims if authorized
     */
    public static function requireRole(string $role): ?object {
        if (!self::isEnabled()) {
            return null;
        }

        $claims = self::requireAuth();
        $userRole = $claims->app_metadata->role ?? 'user';

        if ($userRole !== $role) {
            http_response_code(403);
            die('<h1>403 — Access Denied</h1><p>You do not have permission.</p>');
        }

        return $claims;
    }

    /**
     * Get current user claims without redirecting. Returns null if not authenticated.
     *
     * @return object|null Decoded JWT payload or null
     */
    public static function currentUser(): ?object {
        if (self::$cachedClaims !== null) {
            return self::$cachedClaims;
        }

        $token = self::extractToken();
        if ($token === null) {
            return null;
        }

        $claims = self::verifyToken($token);
        if ($claims !== null) {
            self::$cachedClaims = $claims;
        }
        return $claims;
    }

    // --- Private helpers ---

    /**
     * Extract JWT from Authorization header or cookie
     */
    private static function extractToken(): ?string {
        // 1. Authorization header (API calls from JS)
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        // 2. Cookie (page navigation — set by auth.js)
        return $_COOKIE['sb-access-token'] ?? null;
    }

    /**
     * Verify HS256 JWT with built-in PHP functions. No external libraries.
     *
     * @param string $token Raw JWT string
     * @return object|null Decoded payload or null if invalid
     */
    private static function verifyToken(string $token): ?object {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // Verify HS256 signature
        $signature = self::base64UrlDecode($signatureB64);
        $expected = hash_hmac('sha256', "{$headerB64}.{$payloadB64}", SUPABASE_JWT_SECRET, true);

        if (!hash_equals($expected, $signature)) {
            return null;
        }

        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadB64));
        if ($payload === null) {
            return null;
        }

        // Validate expiry
        if (($payload->exp ?? 0) < time()) {
            return null;
        }

        // Validate audience and issuer
        if (($payload->aud ?? '') !== JWT_AUDIENCE) {
            return null;
        }
        if (($payload->iss ?? '') !== JWT_ISSUER) {
            return null;
        }

        return $payload;
    }

    /**
     * Base64 URL-safe decode
     */
    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Deny access — JSON 401 for API requests, redirect for page requests
     */
    private static function denyAccess(): never {
        // JSON API request → return JSON error
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($contentType, 'json') || str_contains($accept, 'json')) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        // Page request → redirect to login
        $intended = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: /auth/login.php?redirect=' . urlencode($intended));
        exit;
    }
}
