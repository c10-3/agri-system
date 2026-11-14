<?php
declare(strict_types=1);

// Load .env without Composer, and let host environment variables override it
$envFile = __DIR__ . '/../.env';
$env = (is_readable($envFile) ? @parse_ini_file($envFile) : []) ?: [];
foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'APP_BASE_URL'] as $key) {
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        $env[$key] = $value;
    }
}

// Never show PHP errors on screen (they are still written to the server log)
ini_set('display_errors', '0');

date_default_timezone_set('Asia/Manila');

define('APP_NAME', 'Agriculture Information System');
// No trailing slash: every file adds its own "/" after BASE_URL
define('BASE_URL', rtrim($env['APP_BASE_URL'] ?? '/agri-system', '/'));
define('PAGE_SIZE', 10);

// Database settings, read by config/db.php
define('DB_CONFIG', [
    'host' => $env['DB_HOST']     ?? '',
    'port' => $env['DB_PORT']     ?? '5432',
    'name' => $env['DB_NAME']     ?? 'postgres',
    'user' => $env['DB_USER']     ?? '',
    'pass' => $env['DB_PASSWORD'] ?? '',
]);