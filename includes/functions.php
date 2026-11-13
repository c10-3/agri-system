<?php declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

function e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/** Count rows in a table; returns null if the table name differs from your schema. */
function safe_count(string $table): ?int
{
    if (!preg_match('/^[a-z_]+$/', $table)) {
        return null;
    }
    try {
        return (int) get_pdo()->query("SELECT COUNT(*) FROM \"$table\"")->fetchColumn();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return null;
    }
}

/** Returns [page, offset, totalPages] for pagination. */
function paginate(int $total, int $perPage = PAGE_SIZE): array
{
    $pages  = max(1, (int) ceil($total / $perPage));
    $page   = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);
    return [$page, ($page - 1) * $perPage, $pages];
}

/**
 * Run a SELECT with bound parameters and return all rows.
 * Returns [] on failure and logs the real error instead of showing it to the user.
 */
function db_rows(string $sql, array $params = []): array
{
    try {
        $stmt = get_pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return [];
    }
}

/** Run a SELECT and return the first column of the first row (or null). */
function db_value(string $sql, array $params = []): mixed
{
    $rows = db_rows($sql, $params);
    return $rows ? reset($rows[0]) : null;
}

/* ==================================================================
 * Module helpers (merged from modules/_common.php)
 * ================================================================== */

/* ---------- Database ---------- */
function pdo(): PDO
{
    return auth_db();
}

/* ---------- Formatting ---------- */
function fmt_date(?string $d): string
{
    return $d ? date('M j, Y', strtotime($d)) : '—';
}

function fmt_num($n, int $dec = 2): string
{
    return ($n === null || $n === '') ? '—' : number_format((float) $n, $dec);
}

function fmt_money($n): string
{
    return ($n === null || $n === '') ? '—' : '₱' . number_format((float) $n, 2);
}

/* ---------- Reading filters from the URL ---------- */
function get_str(string $key): string
{
    return trim((string) ($_GET[$key] ?? ''));
}

function get_int(string $key): ?int
{
    $v = filter_var($_GET[$key] ?? null, FILTER_VALIDATE_INT);
    return ($v === false || $v === null) ? null : (int) $v;
}

function valid_date_str(string $s): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d !== false && $d->format('Y-m-d') === $s;
}

function get_date(string $key): ?string
{
    $v = get_str($key);
    return ($v !== '' && valid_date_str($v)) ? $v : null;
}

function like_param(string $q): string
{
    return '%' . str_replace(['\\', '%', ''], ['\\\\', '\\%', '\\'], $q) . '%';
}

/* ---------- Sorting and pagination ---------- */
function query_url(array $override = []): string
{
    $params = array_merge($_GET, $override);
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null) {
            unset($params[$k]);
        }
    }
    return '?' . http_build_query($params);
}

/** Returns [sort key, direction, "ORDER BY" expression]. */
function sort_params(array $allowed, string $default, string $defaultDir = 'asc'): array
{
    $sort = (string) ($_GET['sort'] ?? $default);
    if (!isset($allowed[$sort])) {
        $sort = $default;
    }
    $dir = strtolower((string) ($_GET['dir'] ?? $defaultDir)) === 'desc' ? 'desc' : 'asc';
    return [$sort, $dir, $allowed[$sort] . ' ' . strtoupper($dir)];
}

function sort_header(string $label, string $col, string $sort, string $dir): string
{
    $next  = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
    $arrow = $sort === $col ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
    return '<th><a class="text-decoration-none text-dark" href="'
        . e(query_url(['sort' => $col, 'dir' => $next, 'page' => 1])) . '">'
        . e($label) . $arrow . '</a></th>';
}

/** Runs COUNT + a paginated SELECT. Returns [rows, total, page, pages]. */
function paged_query(string $select, string $from, string $where, array $params, string $order): array
{
    $pdo  = pdo();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $from $where");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    [$page, $offset, $pages] = paginate($total);

    $stmt = $pdo->prepare("SELECT $select FROM $from $where ORDER BY $order LIMIT :lim OFFSET :off");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':lim', PAGE_SIZE, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return [$stmt->fetchAll(), $total, $page, $pages];
}

function render_pager(int $page, int $pages): void
{
    if ($pages <= 1) {
        return;
    }
    $item = function (string $label, int $p, bool $disabled = false, bool $active = false): string {
        $cls = 'page-item' . ($disabled ? ' disabled' : '') . ($active ? ' active' : '');
        $url = $disabled ? '#' : e(query_url(['page' => $p]));
        return '<li class="' . $cls . '"><a class="page-link" href="' . $url . '">' . $label . '</a></li>';
    };
    echo '<nav aria-label="Pagination"><ul class="pagination pagination-sm justify-content-center flex-wrap mb-0">';
    echo $item('&laquo; Prev', $page - 1, $page <= 1);
    for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++) {
        echo $item((string) $p, $p, false, $p === $page);
    }
    echo $item('Next &raquo;', $page + 1, $page >= $pages);
    echo '</ul></nav>';
}

function record_range(int $total, int $page): string
{
    if ($total === 0) {
        return 'No records found';
    }
    $from = ($page - 1) * PAGE_SIZE + 1;
    $to   = min($total, $page * PAGE_SIZE);
    return "Showing {$from}–{$to} of {$total}";
}

/* ---------- Forms ---------- */
function field_class(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' is-invalid' : '';
}

function field_error(array $errors, string $field): string
{
    return isset($errors[$field])
        ? '<div class="invalid-feedback">' . e($errors[$field]) . '</div>'
        : '';
}

function select_options(array $rows, string $idKey, string $labelKey, $selected, string $placeholder = '-- Select --'): string
{
    $html = '<option value="">' . e($placeholder) . '</option>';
    foreach ($rows as $r) {
        $sel   = ((string) $selected === (string) $r[$idKey]) ? ' selected' : '';
        $html .= '<option value="' . e((string) $r[$idKey]) . '"' . $sel . '>' . e((string) $r[$labelKey]) . '</option>';
    }
    return $html;
}

function lookup_rows(string $table, string $idCol, string $labelCol, string $orderBy = ''): array
{
    if (!preg_match('/^[a-z_]+$/', $table . $idCol . $labelCol)) {
        return [];
    }
    $order = $orderBy !== '' ? $orderBy : $labelCol;
    return pdo()->query("SELECT $idCol, $labelCol FROM $table ORDER BY $order")->fetchAll();
}

function row_exists(string $table, string $idCol, ?int $id): bool
{
    if ($id === null || !preg_match('/^[a-z_]+$/', $table . $idCol)) {
        return false;
    }
    $stmt = pdo()->prepare("SELECT 1 FROM $table WHERE $idCol = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    return (bool) $stmt->fetchColumn();
}

function value_taken(string $table, string $col, string $val, string $idCol, ?int $ignoreId): bool
{
    $sql = "SELECT 1 FROM $table WHERE $col = :v";
    $p   = [':v' => $val];
    if ($ignoreId !== null) {
        $sql .= " AND $idCol <> :id";
        $p[':id'] = $ignoreId;
    }
    $stmt = pdo()->prepare($sql . ' LIMIT 1');
    $stmt->execute($p);
    return (bool) $stmt->fetchColumn();
}

/** "12.50" -> 12.5, anything else -> null. Fits NUMERIC(10,2). */
function parse_decimal(string $s): ?float
{
    return preg_match('/^\d{1,8}(\.\d{1,2})?$/', $s) ? (float) $s : null;
}

function posted_id(string $key = 'id'): ?int
{
    $v = filter_var($_POST[$key] ?? ($_GET[$key] ?? null), FILTER_VALIDATE_INT);
    return ($v === false || $v === null || $v < 1) ? null : (int) $v;
}

/** Stops the request unless it is a POST with a valid CSRF token. */
function require_valid_post(string $backTo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect($backTo);
    }
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        set_flash('danger', 'Your session token was invalid. Please try again.');
        redirect($backTo);
    }
}

/** A friendly message for a failed save. Real details go to the server log only. */
function save_error_message(PDOException $e, string $uniqueMessage): string
{
    error_log($e->getMessage());
    return $e->getCode() === '23505'
        ? $uniqueMessage
        : 'The record could not be saved. Please check your input and try again.';
}

/* ---------- Deleting ---------- */
function delete_form(int $id, string $confirmMessage): string
{
    return '<form method="post" action="delete.php" class="d-inline">'
        . csrf_field()
        . '<input type="hidden" name="id" value="' . $id . '">'
        . '<button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="' . e($confirmMessage) . '">Delete</button>'
        . '</form>';
}

/**
 * Shared delete handler. $dependents is [label => [table, column]] and
 * blocks the delete (with a clear message) while related records exist.
 */
function handle_delete(string $module, string $table, string $idCol, string $singular, array $dependents = []): never
{
    require_role([ROLE_ADMIN]);
    $back = "modules/$module/index.php";
    require_valid_post($back);

    $id = posted_id();
    if ($id === null) {
        set_flash('danger', 'No record was selected.');
        redirect($back);
    }

    $pdo = pdo();
    foreach ($dependents as $label => [$depTable, $depCol]) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $depTable WHERE $depCol = :id");
        $stmt->execute([':id' => $id]);
        $n = (int) $stmt->fetchColumn();
        if ($n > 0) {
            set_flash('danger', "This $singular cannot be deleted because it still has $n $label record(s). Delete or reassign those first.");
            redirect($back);
        }
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE $idCol = :id");
        $stmt->execute([':id' => $id]);
        if ($stmt->rowCount() > 0) {
            set_flash('success', ucfirst($singular) . ' deleted successfully.');
        } else {
            set_flash('warning', 'That record no longer exists.');
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        set_flash('danger', "This $singular cannot be deleted because other records depend on it.");
    }
    redirect($back);
}

/* ---------- Display helpers ---------- */
function crop_label(array $r): string
{
    return $r['crop_name'] . (($r['variety'] ?? '') !== '' && $r['variety'] !== null ? ' (' . $r['variety'] . ')' : '');
}

/** Character count that also works when the mbstring extension is off. */
function text_len(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s) : (int) preg_match_all('/./su', $s);
}
