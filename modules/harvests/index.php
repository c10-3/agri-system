<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$q        = get_str('q');
$gradeId  = get_int('grade_id');
$remarkId = get_int('remark_id');
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
if ($gradeId)  { $where[] = 'h.grade_id = :gid';  $params[':gid'] = $gradeId; }
if ($remarkId) { $where[] = 'h.remark_id = :rid'; $params[':rid'] = $remarkId; }
if ($farmId)   { $where[] = 'p.farm_id = :fid';   $params[':fid'] = $farmId; }
if ($cropId)   { $where[] = 'p.crop_id = :cid';   $params[':cid'] = $cropId; }
if ($from)     { $where[] = 'h.harvest_date >= :dfrom'; $params[':dfrom'] = $from; }
if ($to)       { $where[] = 'h.harvest_date <= :dto';   $params[':dto']   = $to; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$from_sql = 'harvest h JOIN planting p ON p.planting_id = h.planting_id
                       JOIN farm fm ON fm.farm_id = p.farm_id
                       JOIN farmer fr ON fr.farmer_id = fm.farmer_id
                       JOIN crop c ON c.crop_id = p.crop_id
                       LEFT JOIN quality_grade qg ON qg.grade_id = h.grade_id
                       JOIN harvest_remark hr ON hr.remark_id = h.remark_id';

[$sort, $dir, $order] = sort_params([
    'date'     => 'h.harvest_date',
    'farm'     => 'fm.farm_name',
    'crop'     => 'c.crop_name',
    'quantity' => 'h.quantity_kg',
    'price'    => 'h.selling_price_per_kg',
    'value'    => '(h.quantity_kg * h.selling_price_per_kg)',
    'grade'    => 'qg.grade_name',
    'remark'   => 'hr.remark_description',
], 'date', 'desc');
$order .= ', h.harvest_id DESC';

[$rows, $total, $page, $pages] = paged_query(
    "h.harvest_id, h.harvest_date, h.quantity_kg, h.selling_price_per_kg, (h.quantity_kg * h.selling_price_per_kg) AS est_value,
     fm.farm_name, c.crop_name, c.variety, qg.grade_name, hr.remark_description",
    $from_sql, $whereSql, $params, $order
);

// Totals for everything that matches the filters (not just this page)
$stmt = pdo()->prepare("SELECT COALESCE(SUM(h.quantity_kg), 0), COALESCE(SUM(h.quantity_kg * h.selling_price_per_kg), 0) FROM $from_sql $whereSql");
$stmt->execute($params);
[$sumQty, $sumValue] = $stmt->fetch(PDO::FETCH_NUM);

$lookups = harvest_lookups();

$pageTitle = 'Harvests';
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Harvests</h1>
    <a href="add.php" class="btn btn-success">+ Add Harvest</a>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small mb-1">Search</label>
            <input type="text" name="q" class="form-control" placeholder="Farm, crop or farmer" value="<?= e($q) ?>"></div>
        <div class="col-md-3"><label class="form-label small mb-1">Farm</label>
            <select name="farm_id" class="form-select"><?= select_options($lookups['farms'], 'farm_id', 'farm_name', (string) $farmId, 'All farms') ?></select></div>
        <div class="col-md-3"><label class="form-label small mb-1">Crop</label>
            <select name="crop_id" class="form-select"><?= select_options($lookups['crops'], 'crop_id', 'label', (string) $cropId, 'All crops') ?></select></div>
        <div class="col-md-3"><label class="form-label small mb-1">Quality grade</label>
            <select name="grade_id" class="form-select"><?= select_options($lookups['grades'], 'grade_id', 'grade_name', (string) $gradeId, 'All grades') ?></select></div>
        <div class="col-md-3"><label class="form-label small mb-1">Remark</label>
            <select name="remark_id" class="form-select"><?= select_options($lookups['remarks'], 'remark_id', 'remark_description', (string) $remarkId, 'All remarks') ?></select></div>
        <div class="col-md-2"><label class="form-label small mb-1">Harvested from</label>
            <input type="date" name="from" class="form-control" value="<?= e((string) $from) ?>"></div>
        <div class="col-md-2"><label class="form-label small mb-1">Harvested to</label>
            <input type="date" name="to" class="form-control" value="<?= e((string) $to) ?>"></div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-success flex-fill" type="submit">Filter</button>
            <a href="index.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-6"><div class="card shadow-sm stat-card text-center"><div class="card-body py-2">
        <div class="text-muted small">Total quantity (matching records)</div><div class="h4 mb-0"><?= e(fmt_num($sumQty)) ?> kg</div></div></div></div>
    <div class="col-6"><div class="card shadow-sm stat-card text-center"><div class="card-body py-2">
        <div class="text-muted small">Estimated value (matching records)</div><div class="h4 mb-0"><?= e(fmt_money($sumValue)) ?></div></div></div></div>
</div>

<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr>
            <?= sort_header('Date', 'date', $sort, $dir) ?>
            <?= sort_header('Farm', 'farm', $sort, $dir) ?>
            <?= sort_header('Crop', 'crop', $sort, $dir) ?>
            <?= sort_header('Qty (kg)', 'quantity', $sort, $dir) ?>
            <?= sort_header('Price / kg', 'price', $sort, $dir) ?>
            <?= sort_header('Est. value', 'value', $sort, $dir) ?>
            <?= sort_header('Grade', 'grade', $sort, $dir) ?>
            <?= sort_header('Remark', 'remark', $sort, $dir) ?>
            <th class="text-end">Actions</th>
        </tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">No harvest records found.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e(fmt_date($r['harvest_date'])) ?></td>
                <td><?= e($r['farm_name']) ?></td>
                <td><?= e(crop_label($r)) ?></td>
                <td><?= e(fmt_num($r['quantity_kg'])) ?></td>
                <td><?= e(fmt_money($r['selling_price_per_kg'])) ?></td>
                <td><?= e(fmt_money($r['est_value'])) ?></td>
                <td><?= e((string) $r['grade_name']) ?></td>
                <td><?= e($r['remark_description']) ?></td>
                <td class="text-end text-nowrap">
                    <a href="view.php?id=<?= (int) $r['harvest_id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    <a href="edit.php?id=<?= (int) $r['harvest_id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    <?php if (is_admin()): ?><?= delete_form((int) $r['harvest_id'], 'Delete this harvest record? This cannot be undone.') ?><?php endif; ?>
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
