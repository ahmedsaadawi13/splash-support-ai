<?php
// FILE: /config/config.php

/**
 * SplashSupportAI - Configuration File
 * Loads environment variables and defines application constants
 */

// Load .env file
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Helper function to get env values
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    // Convert string booleans
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'empty':
        case '(empty)':
            return '';
        case 'null':
        case '(null)':
            return null;
    }

    return $value;
}

// Define application constants
define('APP_NAME', env('APP_NAME', 'SplashSupportAI'));
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', env('APP_DEBUG', false));
define('BASE_URL', rtrim(env('BASE_URL', 'http://localhost'), '/'));

// Database
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'splash_support_ai'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('UPLOAD_PATH', STORAGE_PATH . '/uploads');
define('LOG_PATH', STORAGE_PATH . '/logs');

// Security
define('SESSION_LIFETIME', env('SESSION_LIFETIME', 7200));
define('CSRF_TOKEN_NAME', env('CSRF_TOKEN_NAME', 'csrf_token'));

// File Uploads
define('MAX_UPLOAD_SIZE', env('MAX_UPLOAD_SIZE', 10485760)); // 10MB default
define('ALLOWED_MIME_TYPES', env('ALLOWED_MIME_TYPES', 'image/jpeg,image/png,image/gif,application/pdf'));

// AI
define('AI_PROVIDER', env('AI_PROVIDER', 'openai'));
define('AI_PROVIDER_KEY', env('AI_PROVIDER_KEY', ''));
define('AI_MODEL', env('AI_MODEL', 'gpt-4'));
define('AI_MAX_TOKENS', env('AI_MAX_TOKENS', 1000));

// Pagination
define('ITEMS_PER_PAGE', env('ITEMS_PER_PAGE', 25));

// Timezone
define('DEFAULT_TIMEZONE', env('DEFAULT_TIMEZONE', 'UTC'));
date_default_timezone_set(DEFAULT_TIMEZONE);

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
