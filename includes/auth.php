<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

require_once __DIR__ . '/functions.php';

// Safety net in case your functions.php doesn't define these
if (!function_exists('e')) {
    function e(?string $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('get_flash')) {
    function get_flash(): ?array
    {
        $f = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $f;
    }
}

// Must match role.role_name values in your role table
const ROLE_ADMIN = 'Administrator';
const ROLE_STAFF = 'Staff';

const SESSION_TIMEOUT = 1800; // 30 min idle
const MAX_ATTEMPTS    = 5;
const LOCKOUT_SECONDS = 300;

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}


/** Works with whichever connection style config/db.php uses. */
function auth_db(): PDO
{
    if (function_exists('get_pdo')) {
        return get_pdo();
    }
    if (function_exists('db')) {
        return db();
    }
    foreach (['pdo', 'conn', 'db'] as $name) {
        if (isset($GLOBALS[$name]) && $GLOBALS[$name] instanceof PDO) {
            return $GLOBALS[$name];
        }
    }
    throw new RuntimeException('No PDO connection found. Check config/db.php.');
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_valid(?string $token): bool
{
    return is_string($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
}

/* ---------- Login / logout ---------- */
function is_locked_out(): bool
{
    return ($_SESSION['login_attempts'] ?? 0) >= MAX_ATTEMPTS
        && (time() - ($_SESSION['last_attempt'] ?? 0)) < LOCKOUT_SECONDS;
}

function attempt_login(string $username, string $password): bool
{
    if (is_locked_out()) {
        return false;
    }

    $stmt = auth_db()->prepare(
        'SELECT u.user_id, u.username, u.password_hash, u.full_name, u.is_active, r.role_name
           FROM users u
           JOIN role r ON r.role_id = u.role_id
          WHERE u.username = :username
          LIMIT 1'
    );
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    // Always run password_verify so timing doesn't reveal whether the username exists
    $hash = $user['password_hash'] ?? '$2y$10$usesomesillystringforsaltuOq1Kz8cEJ1l5uI3Zc1o5Fq0kZ1G6';
    $ok   = password_verify($password, $hash);

    if (!$user || !$ok || !$user['is_active']) {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['last_attempt']   = time();
        return false;
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        auth_db()->prepare('UPDATE users SET password_hash = :h WHERE user_id = :id')
            ->execute([':h' => password_hash($password, PASSWORD_DEFAULT), ':id' => $user['user_id']]);
    }

    session_regenerate_id(true);
    $_SESSION = [
        'user_id'       => (int) $user['user_id'],
        'username'      => $user['username'],
        'full_name'     => $user['full_name'],
        'role'          => $user['role_name'],
        'last_activity' => time(),
    ];
    return true;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ---------- Helpers ---------- */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array
{
    return is_logged_in() ? [
        'id'        => $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role'      => $_SESSION['role'],
    ] : null;
}

function is_admin(): bool
{
    return ($_SESSION['role'] ?? '') === ROLE_ADMIN;
}

/* ---------- Guards ---------- */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    if (time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT) {
        logout();
        session_start();
        $_SESSION['flash_error'] = 'Your session expired. Please log in again.';
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

/** Usage: require_role([ROLE_ADMIN]); */
function require_role(array $roles): void
{
    require_login();
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1">'
           . '<title>Access Denied</title>'
           . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
           . '<body class="bg-light"><div class="container min-vh-100 d-flex align-items-center justify-content-center text-center">'
           . '<div><h1 class="display-4">403</h1><p class="lead">You do not have permission to access this page.</p>'
           . '<a href="' . BASE_URL . '/dashboard.php" class="btn btn-success">Back to Dashboard</a></div></div></body></html>';
        exit;
    }
}
