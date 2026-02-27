<?php
/**
 * Supabase Auth Middleware
 *
 * Verifies JWTs using:
 *   1. JWKS (RS256/ES256) — fetches public keys from Supabase's JWKS endpoint
 *   2. HS256 fallback — uses SUPABASE_JWT_SECRET if configured
 *
 * All verification uses built-in PHP functions (openssl_verify, hash_hmac).
 * No external dependencies required.
 *
 * If Supabase is not configured (SUPABASE_URL empty), auth is skipped
 * and .htaccess HTTP Basic Auth is assumed to be handling protection.
 */

class AuthMiddleware {
    private static ?object $cachedClaims = null;
    private static ?array $jwksCache = null;

    /** JWKS cache file lives in data/ (already protected by .htaccess) */
    private const JWKS_CACHE_TTL = 3600; // 1 hour

    /**
     * Check if Supabase auth is enabled.
     * Only requires SUPABASE_URL — JWKS keys are fetched automatically.
     * SUPABASE_JWT_SECRET is optional (legacy HS256 fallback).
     */
    public static function isEnabled(): bool {
        return defined('SUPABASE_URL') && SUPABASE_URL !== '';
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
     * Verify JWT signature and claims.
     * Dispatches to RS256/ES256 (via JWKS) or HS256 based on the JWT header's alg field.
     */
    private static function verifyToken(string $token): ?object {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // Decode JWT header to determine algorithm
        $jwtHeader = json_decode(self::base64UrlDecode($headerB64));
        if ($jwtHeader === null) {
            return null;
        }

        $alg = $jwtHeader->alg ?? '';
        $kid = $jwtHeader->kid ?? null;
        $signature = self::base64UrlDecode($signatureB64);
        $signedData = "{$headerB64}.{$payloadB64}";

        // Verify signature based on algorithm
        $signatureValid = false;

        if ($alg === 'RS256' || $alg === 'ES256') {
            $signatureValid = self::verifyAsymmetric($alg, $kid, $signedData, $signature);
        } elseif ($alg === 'HS256') {
            $signatureValid = self::verifyHS256($signedData, $signature);
        }

        if (!$signatureValid) {
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
     * Verify HS256 signature using legacy JWT secret.
     * Falls back to false if SUPABASE_JWT_SECRET is not configured.
     */
    private static function verifyHS256(string $signedData, string $signature): bool {
        if (!defined('SUPABASE_JWT_SECRET') || SUPABASE_JWT_SECRET === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $signedData, SUPABASE_JWT_SECRET, true);
        return hash_equals($expected, $signature);
    }

    /**
     * Verify RS256 or ES256 signature using JWKS public keys.
     * Fetches keys from Supabase's JWKS endpoint, caches to file.
     */
    private static function verifyAsymmetric(string $alg, ?string $kid, string $signedData, string $signature): bool {
        $jwks = self::getJwks();
        if ($jwks === null) {
            return false;
        }

        // Find the matching key by kid, or try all keys if no kid
        $keysToTry = [];
        foreach ($jwks as $key) {
            if ($kid !== null && isset($key['kid']) && $key['kid'] === $kid) {
                $keysToTry = [$key];
                break;
            }
            if ($kid === null) {
                $keysToTry[] = $key;
            }
        }

        // If kid was specified but not found, try all keys as fallback
        if (empty($keysToTry)) {
            $keysToTry = $jwks;
        }

        foreach ($keysToTry as $key) {
            $keyAlg = $key['alg'] ?? '';
            if ($keyAlg !== $alg) {
                continue;
            }

            $pem = self::jwkToPem($key);
            if ($pem === null) {
                continue;
            }

            $opensslAlg = ($alg === 'RS256') ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA256;

            if ($alg === 'ES256') {
                // ES256 uses DER-encoded signature, JWT uses raw R||S
                $derSignature = self::ecRawToDer($signature);
                if ($derSignature === null) {
                    continue;
                }
                $result = openssl_verify($signedData, $derSignature, $pem, OPENSSL_ALGO_SHA256);
            } else {
                $result = openssl_verify($signedData, $signature, $pem, OPENSSL_ALGO_SHA256);
            }

            if ($result === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get JWKS keys — from memory cache, file cache, or fresh fetch.
     *
     * @return array|null Array of JWK key objects, or null on failure
     */
    private static function getJwks(): ?array {
        // 1. Memory cache (same request)
        if (self::$jwksCache !== null) {
            return self::$jwksCache;
        }

        // 2. File cache
        $cacheFile = self::getJwksCachePath();
        if ($cacheFile !== null && file_exists($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached !== null
                && isset($cached['fetched_at'], $cached['keys'])
                && (time() - $cached['fetched_at']) < self::JWKS_CACHE_TTL
            ) {
                self::$jwksCache = $cached['keys'];
                return self::$jwksCache;
            }
        }

        // 3. Fetch from Supabase JWKS endpoint
        $jwksUrl = rtrim(SUPABASE_URL, '/') . '/auth/v1/.well-known/jwks.json';
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'method' => 'GET',
                'header' => "Accept: application/json\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $response = @file_get_contents($jwksUrl, false, $context);
        if ($response === false) {
            logError("JWKS fetch failed from: {$jwksUrl}");
            // Fall back to expired cache if available
            if (isset($cached['keys'])) {
                self::$jwksCache = $cached['keys'];
                return self::$jwksCache;
            }
            return null;
        }

        $jwksData = json_decode($response, true);
        if ($jwksData === null || !isset($jwksData['keys']) || !is_array($jwksData['keys'])) {
            logError("JWKS response invalid from: {$jwksUrl}");
            return null;
        }

        self::$jwksCache = $jwksData['keys'];

        // Save to file cache
        if ($cacheFile !== null) {
            $cacheDir = dirname($cacheFile);
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }
            @file_put_contents($cacheFile, json_encode([
                'fetched_at' => time(),
                'keys' => $jwksData['keys'],
            ]));
        }

        return self::$jwksCache;
    }

    /**
     * Get the JWKS cache file path (in data/ directory).
     */
    private static function getJwksCachePath(): ?string {
        $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
        return $root . '/data/jwks-cache.json';
    }

    /**
     * Convert a JWK (JSON Web Key) to PEM format for openssl_verify.
     * Supports RSA (kty=RSA) and EC (kty=EC, crv=P-256) keys.
     */
    private static function jwkToPem(array $jwk): ?string {
        $kty = $jwk['kty'] ?? '';

        if ($kty === 'RSA') {
            return self::rsaJwkToPem($jwk);
        }

        if ($kty === 'EC') {
            return self::ecJwkToPem($jwk);
        }

        return null;
    }

    /**
     * Convert RSA JWK to PEM public key.
     */
    private static function rsaJwkToPem(array $jwk): ?string {
        if (!isset($jwk['n'], $jwk['e'])) {
            return null;
        }

        $n = self::base64UrlDecode($jwk['n']);
        $e = self::base64UrlDecode($jwk['e']);

        // Encode as ASN.1 DER
        $nEncoded = self::asn1Integer($n);
        $eEncoded = self::asn1Integer($e);

        // RSA public key sequence
        $rsaKey = self::asn1Sequence($nEncoded . $eEncoded);

        // BitString wrapper
        $bitString = chr(0x03) . self::asn1Length(strlen($rsaKey) + 1) . chr(0x00) . $rsaKey;

        // Algorithm identifier for RSA
        // OID 1.2.840.113549.1.1.1 (rsaEncryption) + NULL
        $algorithmId = self::asn1Sequence(
            chr(0x06) . chr(0x09) . "\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01" . chr(0x05) . chr(0x00)
        );

        // SubjectPublicKeyInfo
        $spki = self::asn1Sequence($algorithmId . $bitString);

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($spki), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    /**
     * Convert EC JWK (P-256 curve) to PEM public key.
     */
    private static function ecJwkToPem(array $jwk): ?string {
        if (!isset($jwk['x'], $jwk['y'])) {
            return null;
        }

        $crv = $jwk['crv'] ?? 'P-256';
        if ($crv !== 'P-256') {
            return null; // Only P-256 (ES256) supported
        }

        $x = self::base64UrlDecode($jwk['x']);
        $y = self::base64UrlDecode($jwk['y']);

        // Pad to 32 bytes each
        $x = str_pad($x, 32, "\0", STR_PAD_LEFT);
        $y = str_pad($y, 32, "\0", STR_PAD_LEFT);

        // Uncompressed EC point: 0x04 || x || y
        $publicKeyPoint = "\x04" . $x . $y;

        // BitString wrapper
        $bitString = chr(0x03) . self::asn1Length(strlen($publicKeyPoint) + 1) . chr(0x00) . $publicKeyPoint;

        // Algorithm identifier: EC + P-256 curve OIDs
        // OID 1.2.840.10045.2.1 (ecPublicKey) + OID 1.2.840.10045.3.1.7 (P-256)
        $algorithmId = self::asn1Sequence(
            chr(0x06) . chr(0x07) . "\x2a\x86\x48\xce\x3d\x02\x01"
            . chr(0x06) . chr(0x08) . "\x2a\x86\x48\xce\x3d\x03\x01\x07"
        );

        // SubjectPublicKeyInfo
        $spki = self::asn1Sequence($algorithmId . $bitString);

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($spki), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    /**
     * Convert EC raw R||S signature (64 bytes for P-256) to DER format.
     * openssl_verify expects DER-encoded ECDSA signatures.
     */
    private static function ecRawToDer(string $raw): ?string {
        if (strlen($raw) !== 64) {
            return null;
        }

        $r = substr($raw, 0, 32);
        $s = substr($raw, 32, 32);

        // Remove leading zeros, then add 0x00 padding if high bit set
        $r = ltrim($r, "\0");
        $s = ltrim($s, "\0");
        if ($r === '') $r = "\0";
        if ($s === '') $s = "\0";
        if (ord($r[0]) & 0x80) $r = "\0" . $r;
        if (ord($s[0]) & 0x80) $s = "\0" . $s;

        $rEncoded = chr(0x02) . chr(strlen($r)) . $r;
        $sEncoded = chr(0x02) . chr(strlen($s)) . $s;

        return chr(0x30) . chr(strlen($rEncoded) + strlen($sEncoded)) . $rEncoded . $sEncoded;
    }

    // --- ASN.1 DER encoding helpers ---

    private static function asn1Integer(string $data): string {
        // Remove leading zeros
        $data = ltrim($data, "\0");
        if ($data === '') $data = "\0";
        // Add padding byte if high bit set (to keep positive)
        if (ord($data[0]) & 0x80) {
            $data = "\0" . $data;
        }
        return chr(0x02) . self::asn1Length(strlen($data)) . $data;
    }

    private static function asn1Sequence(string $data): string {
        return chr(0x30) . self::asn1Length(strlen($data)) . $data;
    }

    private static function asn1Length(int $length): string {
        if ($length < 128) {
            return chr($length);
        }
        $bytes = '';
        $temp = $length;
        while ($temp > 0) {
            $bytes = chr($temp & 0xff) . $bytes;
            $temp >>= 8;
        }
        return chr(0x80 | strlen($bytes)) . $bytes;
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
