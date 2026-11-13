<?php
declare(strict_types=1);

// Load .env without Composer, and let host environment variables override it
$envFile = __DIR__ . '/../.env';
$env = is_readable($envFile) ? parse_ini_file($envFile) : [];
foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'APP_BASE_URL'] as $key) {
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        $env[$key] = $value;
    }
}

date_default_timezone_set('Asia/Manila');

define('APP_NAME', 'Agriculture Information System');
// No trailing slash: every file adds its own "/" after BASE_URL
define('BASE_URL', rtrim($env['APP_BASE_URL'] ?? '/agri-system', '/'));
define('PAGE_SIZE', 10);