<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$q       = get_str('q');
$address = get_str('address');
$from    = get_date('from');
$to      = get_date('to');

$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(fr.first_name ILIKE :q1 OR fr.last_name ILIKE :q2 OR (fr.first_name || ' ' || fr.last_name) ILIKE :q3
                 OR fr.contact_number ILIKE :q4 OR fr.address ILIKE :q5)";
    $like = like_param($q);
    $params += [':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like, ':q5' => $like];
}
if ($address !== '') {
    $where[] = 'fr.address ILIKE :addr';
    $params[':addr'] = like_param($address);
}
if ($from) { $where[] = 'fr.date_registered >= :dfrom'; $params[':dfrom'] = $from; }
if ($to)   { $where[] = 'fr.date_registered <= :dto';   $params[':dto']   = $to; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

[$sort, $dir, $order] = sort_params([
    'name'    => 'fr.last_name, fr.first_name',
    'contact' => 'fr.contact_number',
    'address' => 'fr.address',
    'date'    => 'fr.date_registered',
    'farms'   => 'farm_count',
], 'name');
$order .= ', fr.farmer_id';

[$rows, $total, $page, $pages] = paged_query(
    'fr.farmer_id, fr.first_name, fr.last_name, fr.contact_number, fr.address, fr.date_registered,
     (SELECT COUNT(*) FROM farm f WHERE f.farmer_id = fr.farmer_id) AS farm_count',
    'farmer fr', $whereSql, $params, $order
);

$pageTitle = 'Farmers';
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Farmers</h1>
    <a href="add.php" class="btn btn-success">+ Add Farmer</a>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small mb-1">Search</label>
            <input type="text" name="q" class="form-control" placeholder="Name, contact or address" value="<?= e($q) ?>"></div>
        <div class="col-md-3"><label class="form-label small mb-1">Address contains</label>
            <input type="text" name="address" class="form-control" value="<?= e($address) ?>"></div>
        <div class="col-md-2"><label class="form-label small mb-1">Registered from</label>
            <input type="date" name="from" class="form-control" value="<?= e((string) $from) ?>"></div>
        <div class="col-md-2"><label class="form-label small mb-1">Registered to</label>
            <input type="date" name="to" class="form-control" value="<?= e((string) $to) ?>"></div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-success flex-fill" type="submit">Filter</button>
            <a href="index.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </div>
</form>

<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr>
            <?= sort_header('Name', 'name', $sort, $dir) ?>
            <?= sort_header('Contact', 'contact', $sort, $dir) ?>
            <?= sort_header('Address', 'address', $sort, $dir) ?>
            <?= sort_header('Registered', 'date', $sort, $dir) ?>
            <?= sort_header('Farms', 'farms', $sort, $dir) ?>
            <th class="text-end">Actions</th>
        </tr></thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No farmers found.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['last_name'] . ', ' . $r['first_name']) ?></td>
                <td><?= e((string) $r['contact_number']) ?></td>
                <td><?= e((string) $r['address']) ?></td>
                <td><?= e(fmt_date($r['date_registered'])) ?></td>
                <td><span class="badge text-bg-secondary"><?= (int) $r['farm_count'] ?></span></td>
                <td class="text-end text-nowrap">
                    <a href="view.php?id=<?= (int) $r['farmer_id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    <a href="edit.php?id=<?= (int) $r['farmer_id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    <?php if (is_admin()): ?>
                        <?= delete_form((int) $r['farmer_id'], 'Delete ' . $r['first_name'] . ' ' . $r['last_name'] . '? This cannot be undone.') ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mt-3">
    <small class="text-muted"><?= e(record_range($total, $page)) ?></small>
    <?php render_pager($page, $pages); ?>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
