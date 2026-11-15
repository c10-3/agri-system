<?php
// App-wide settings. Loaded by every page (through includes/functions.php).

// Never show PHP errors on screen (they are still written to the server log)
ini_set('display_errors', '0');

date_default_timezone_set('Asia/Manila');

define('APP_NAME', 'Agri-System');
define('PAGE_SIZE', 10);   // rows per page in the list pages

// The folder the site lives in. Local XAMPP uses the default below;
// a host that serves the site from its own address sets APP_BASE_URL to /
$baseUrl = getenv('APP_BASE_URL');
$baseUrl = ($baseUrl !== false && $baseUrl !== '') ? $baseUrl : '/agri-system';
define('BASE_URL', rtrim('/' . trim($baseUrl, '/'), '/'));

if (session_status() === PHP_SESSION_NONE) {
    // True when the visitor reached the site over HTTPS (also behind a hosting proxy)
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';   // creates $pdo