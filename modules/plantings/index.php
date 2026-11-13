<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$q        = get_str('q');
$statusId = get_int('status_id');
$farmId   = get_int('farm_id');
$cropId   = get_int('crop_id');
$from     = get_date('from');
$to       = get_date('to');

$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(fm.farm_name ILIKE :q1 OR c.crop_name ILIKE :q2 OR c.variety ILIKE :q3
                 OR (fr.first_name || ' ' || fr.last_name) ILIKE :q4)";
    $like = like_param($q);
    $params += [':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like];
}
if ($statusId) { $where[] = 'p.status_id = :sid'; $params[':sid'] = $statusId; }
if ($farmId)   { $where[] = 'p.farm_id = :fid';   $params[':fid'] = $farmId; }
if ($cropId)   { $where[] = 'p.crop_id = :cid';   $params[':cid'] = $cropId; }
if ($from)     { $where[] = 'p.planting_date >= :dfrom'; $params[':dfrom'] = $from; }
if ($to)       { $where[] = 'p.planting_date <= :dto';   $params[':dto']   = $to; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

[$sort, $dir, $order] = sort_params([
    'farm'     => 'fm.farm_name',
    'crop'     => 'c.crop_name',
    'date'     => 'p.planting_date',
    'expected' => 'p.expected_harvest_date',
    'area'     => 'p.area_planted',
    'status'   => 'ps.status_name',
], 'date', 'desc');
$order .= ', p.planting_id DESC';

[$rows, $total, $page, $pages] = paged_query(
    "p.planting_id, p.planting_date, p.expected_harvest_date, p.area_planted, fm.farm_name, c.crop_name, c.variety,
     ps.status_name, fr.first_name || ' ' || fr.last_name AS farmer_name",
    'planting p JOIN farm fm ON fm.farm_id = p.farm_id
                JOIN farmer fr ON fr.farmer_id = fm.farmer_id
                JOIN crop c ON c.crop_id = p.crop_id
                JOIN planting_status ps ON ps.status_id = p.status_id',
    $whereSql, $params, $order
);
$lookups = planting_lookups();

$pageTitle = 'Plantings';
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Plantings</h1>
    <a href="add.php" class="btn btn-success">+ Add Planting</a>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small mb-1">Search</label>
            <input type="text" name="q" class="form-control" placeholder="Farm, crop or farmer" value="<?= e($q) ?>"></div>
        <div class="col-md-3"><label class="form-label small mb-1">Farm</label>
            <select name="farm_id" class="form-select"><?= select_options($lookups['farms'], 'farm_id', 'label', (string) $farmId, 'All farms') ?></select></div>
        <div class="col-md-3"><label class="form-label small mb-1">Crop</label>
            <select name="crop_id" class="form-select"><?= select_options($lookups['crops'], 'crop_id', 'label', (string) $cropId, 'All crops') ?></select></div>
        <div class="col-md-3"><label class="form-label small mb-1">Status</label>
            <select name="status_id" class="form-select"><?= select_options($lookups['statuses'], 'status_id', 'status_name', (string) $statusId, 'All statuses') ?></select></div>
        <div class="col-md-3"><label class="form-label small mb-1">Planted from</label>
            <input type="date" name="from" class="form-control" value="<?= e((string) $from) ?>"></div>
        <div class="col-md-3"><label class="form-label small mb-1">Planted to</label>
            <input type="date" name="to" class="form-control" value="<?= e((string) $to) ?>"></div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-success flex-fill" type="submit">Filter</button>
            <a href="index.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </div>
</form>

<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr>
            <?= sort_header('Farm', 'farm', $sort, $dir) ?>
            <?= sort_header('Crop', 'crop', $sort, $dir) ?>
            <?= sort_header('Planted', 'date', $sort, $dir) ?>
            <?= sort_header('Expected harvest', 'expected', $sort, $dir) ?>
            <?= sort_header('Area (ha)', 'area', $sort, $dir) ?>
            <?= sort_header('Status', 'status', $sort, $dir) ?>
            <th class="text-end">Actions</th>
        </tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">No planting records found.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['farm_name']) ?><div class="small text-muted"><?= e($r['farmer_name']) ?></div></td>
                <td><?= e(crop_label($r)) ?></td>
                <td><?= e(fmt_date($r['planting_date'])) ?></td>
                <td><?= e(fmt_date($r['expected_harvest_date'])) ?></td>
                <td><?= e(fmt_num($r['area_planted'])) ?></td>
                <td><span class="badge text-bg-success"><?= e($r['status_name']) ?></span></td>
                <td class="text-end text-nowrap">
                    <a href="view.php?id=<?= (int) $r['planting_id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    <a href="edit.php?id=<?= (int) $r['planting_id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    <?php if (is_admin()): ?><?= delete_form((int) $r['planting_id'], 'Delete this planting record? This cannot be undone.') ?><?php endif; ?>
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
