<?php
declare(strict_types=1);

// Shared tools for all report pages.
// It loads your existing includes/functions.php (pdo(), require_login(), ...)
// and protects every report page with the login check.
require_once __DIR__ . '/../includes/functions.php';
require_login();

/* ---------- small formatting helpers (prefix rpt_ so they never clash) ---------- */

function rpt_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rpt_money($value): string
{
    return '₱' . number_format((float) $value, 2);
}

function rpt_num($value, int $decimals = 2): string
{
    return number_format((float) $value, $decimals);
}

function rpt_date($value): string
{
    return $value ? date('M j, Y', strtotime((string) $value)) : '—';
}

function rpt_crop_label($name, $variety): string
{
    $variety = trim((string) $variety);
    return (string) $name . ($variety !== '' ? ' (' . $variety . ')' : '');
}

/* ---------- date range filter ---------- */

function rpt_valid_date(string $s): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d !== false && $d->format('Y-m-d') === $s;
}

/** Reads ?from= and ?to= from the address bar. Returns [from, to, error message]. */
function rpt_dates(): array
{
    $from = trim((string) ($_GET['from'] ?? ''));
    $to   = trim((string) ($_GET['to'] ?? ''));
    $err  = '';

    if (($from !== '' && !rpt_valid_date($from)) || ($to !== '' && !rpt_valid_date($to))) {
        $err  = 'Please enter valid dates. The date filter was ignored.';
        $from = $to = '';
    } elseif ($from !== '' && $to !== '' && $from > $to) {
        $err  = 'The "From" date cannot be later than the "To" date. The date filter was ignored.';
        $from = $to = '';
    }
    return [$from, $to, $err];
}

/** Adds "column >= :from" and "column <= :to" to the query conditions. */
function rpt_date_conditions(string $column, string $from, string $to, array &$conds, array &$params): void
{
    if ($from !== '') {
        $conds[]         = "$column >= :from";
        $params[':from'] = $from;
    }
    if ($to !== '') {
        $conds[]       = "$column <= :to";
        $params[':to'] = $to;
    }
}

function rpt_period(string $from, string $to): string
{
    if ($from === '' && $to === '') {
        return 'All dates';
    }
    if ($to === '') {
        return 'From ' . rpt_date($from);
    }
    if ($from === '') {
        return 'Up to ' . rpt_date($to);
    }
    return rpt_date($from) . ' to ' . rpt_date($to);
}

/* ---------- filter form, dropdown, title ---------- */

/** One dropdown for the filter form. $options = [value => label]. */
function rpt_select(string $name, string $label, array $options, string $selected, string $allLabel): string
{
    $html = '<div class="col-sm-6 col-md-3"><label class="form-label" for="' . rpt_h($name) . '">' . rpt_h($label) . '</label>'
          . '<select class="form-select" id="' . rpt_h($name) . '" name="' . rpt_h($name) . '">'
          . '<option value="">' . rpt_h($allLabel) . '</option>';
    foreach ($options as $value => $text) {
        $html .= '<option value="' . rpt_h($value) . '"' . ((string) $value === $selected ? ' selected' : '') . '>'
              . rpt_h($text) . '</option>';
    }
    return $html . '</select></div>';
}

/** The From / To form. $dateLabel says which date is filtered. $extraHtml adds dropdowns. */
function rpt_filter_form(string $from, string $to, string $dateLabel, string $extraHtml = ''): void
{
    ?>
    <form method="get" class="card card-body mb-3 d-print-none">
        <div class="row g-2 align-items-end">
            <div class="col-sm-6 col-md-3">
                <label class="form-label" for="from">From (<?= rpt_h($dateLabel) ?>)</label>
                <input type="date" class="form-control" id="from" name="from" value="<?= rpt_h($from) ?>">
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label" for="to">To (<?= rpt_h($dateLabel) ?>)</label>
                <input type="date" class="form-control" id="to" name="to" value="<?= rpt_h($to) ?>">
            </div>
            <?= $extraHtml ?>
            <div class="col-auto">
                <button type="submit" class="btn btn-success">Apply</button>
                <a class="btn btn-outline-secondary" href="<?= rpt_h(basename($_SERVER['PHP_SELF'])) ?>">Reset</a>
                <button type="button" class="btn btn-outline-dark" onclick="window.print()">Print</button>
            </div>
        </div>
    </form>
    <?php
}

function rpt_title(string $title, string $period, string $dateError): void
{
    echo '<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">';
    echo '<div><h1 class="h3 mb-0">' . rpt_h($title) . '</h1>';
    echo '<div class="text-muted">Period: ' . rpt_h($period) . '</div></div>';
    echo '<a class="btn btn-outline-secondary btn-sm d-print-none" href="index.php">&larr; All reports</a>';
    echo '</div>';
    if ($dateError !== '') {
        echo '<div class="alert alert-warning">' . rpt_h($dateError) . '</div>';
    }
}

/** A row of summary cards: [['Label', 'Value'], ...] */
function rpt_cards(array $cards): void
{
    echo '<div class="row g-3 mb-3">';
    foreach ($cards as [$label, $value]) {
        echo '<div class="col-6 col-md-3"><div class="card text-center h-100"><div class="card-body">'
           . '<div class="text-muted small">' . rpt_h($label) . '</div>'
           . '<div class="h4 mb-0 text-success">' . rpt_h($value) . '</div></div></div></div>';
    }
    echo '</div>';
}

/* ---------- pagination ---------- */

function rpt_page(): int
{
    $p = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
    return ($p !== false && $p >= 1) ? (int) $p : 1;
}

function rpt_url(array $override): string
{
    return '?' . http_build_query(array_merge($_GET, $override));
}

function rpt_pagination(int $total, int $perPage, int $page): void
{
    $pages = (int) ceil($total / $perPage);
    if ($pages <= 1) {
        return;
    }
    $link = function (int $p, string $label, bool $disabled = false, bool $active = false): string {
        $cls = 'page-item' . ($disabled ? ' disabled' : '') . ($active ? ' active' : '');
        return '<li class="' . $cls . '"><a class="page-link" href="' . rpt_h(rpt_url(['page' => $p])) . '">' . $label . '</a></li>';
    };
    echo '<nav class="d-print-none" aria-label="Pages"><ul class="pagination justify-content-center">';
    echo $link(max(1, $page - 1), '&laquo;', $page <= 1);
    for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++) {
        echo $link($i, (string) $i, false, $i === $page);
    }
    echo $link(min($pages, $page + 1), '&raquo;', $page >= $pages);
    echo '</ul></nav>';
}

/* ---------- page layout (uses your includes/header.php and footer.php if they exist) ---------- */

function report_header(string $title): void
{
    $pageTitle = $title;
    $GLOBALS['pageTitle'] = $title;

    $header = __DIR__ . '/../includes/header.php';
    if (is_file($header)) {
        include $header;
    } else {
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1">'
           . '<title>' . rpt_h($title) . '</title>'
           . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
           . '</head><body class="bg-light"><div class="container py-4">';
    }
    // When printing, hide the menu, buttons and filters.
    echo '<style>@media print{nav,.navbar,footer,.d-print-none{display:none!important}}</style>';
}

function report_footer(): void
{
    $footer = __DIR__ . '/../includes/footer.php';
    if (is_file($footer)) {
        include $footer;
    } else {
        echo '</div></body></html>';
    }
}
