<?php
/**
 * Environment Variable Loader
 *
 * Loads .env file for local development and provides env() helper.
 * In production (Coolify), environment variables are already set.
 *
 * Loading priority per variable:
 * 1. Real environment variable (Coolify, Docker, system)
 * 2. .env file value (local development)
 * 3. Default value passed to env()
 */

/**
 * Get an environment variable with optional default
 *
 * @param string $key Variable name
 * @param mixed $default Default value if not found
 * @return mixed
 */
function env($key, $default = null) {
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    // Convert common string representations
    switch (strtolower($value)) {
        case 'true':  return true;
        case 'false': return false;
        case 'null':  return null;
    }

    return $value;
}

/**
 * Load a .env file into environment variables
 * System/Coolify env vars are never overwritten.
 *
 * @param string $path Path to .env file
 */
function loadEnvFile($path) {
    if (!file_exists($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') === false) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Strip surrounding quotes
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        // Don't overwrite existing env vars (system/Coolify vars take priority)
        if (getenv($key) === false) {
            putenv("{$key}={$value}");
        }
    }
}

/**
 * Validate that required configuration constants are set and non-empty
 *
 * @param array $required List of constant names to check
 */
function validateConfig($required = []) {
    $missing = [];
    foreach ($required as $name) {
        if (!defined($name) || (is_string(constant($name)) && constant($name) === '')) {
            $missing[] = $name;
        }
    }

    if (!empty($missing)) {
        $list = implode(', ', $missing);
        die("Missing required configuration: {$list}. "
          . "Set these in .env (local dev), config.php (shared hosting), "
          . "or environment variables (Coolify).");
    }
}
