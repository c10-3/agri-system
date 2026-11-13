<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$q = get_str('q');
$farmerId = get_int('farmer_id');
$soilId   = get_int('soil_type_id');
$irrId    = get_int('irrigation_id');

$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(f.farm_name ILIKE :q1 OR f.location ILIKE :q2 OR fr.first_name ILIKE :q3 OR fr.last_name ILIKE :q4
                 OR (fr.first_name || ' ' || fr.last_name) ILIKE :q5)";
    $like = like_param($q);
    $params += [':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like, ':q5' => $like];
}
if ($farmerId) { $where[] = 'f.farmer_id = :fid';       $params[':fid']  = $farmerId; }
if ($soilId)   { $where[] = 'f.soil_type_id = :sid';    $params[':sid']  = $soilId; }
if ($irrId)    { $where[] = 'f.irrigation_id = :iid';   $params[':iid']  = $irrId; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

[$sort, $dir, $order] = sort_params([
    'farm'       => 'f.farm_name',
    'farmer'     => 'fr.last_name, fr.first_name',
    'location'   => 'f.location',
    'area'       => 'f.area_hectares',
    'soil'       => 's.soil_type_name',
    'irrigation' => 'i.irrigation_name',
], 'farm');
$order .= ', f.farm_id';

[$rows, $total, $page, $pages] = paged_query(
    "f.farm_id, f.farm_name, f.location, f.area_hectares, fr.first_name || ' ' || fr.last_name AS farmer_name,
     s.soil_type_name, i.irrigation_name",
    'farm f JOIN farmer fr ON fr.farmer_id = f.farmer_id
            LEFT JOIN soil_type s ON s.soil_type_id = f.soil_type_id
            LEFT JOIN irrigation_type i ON i.irrigation_id = f.irrigation_id',
    $whereSql, $params, $order
);
$lookups = farm_lookups();

$pageTitle = 'Farms';
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Farms</h1>
    <a href="add.php" class="btn btn-success">+ Add Farm</a>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small mb-1">Search</label>
            <input type="text" name="q" class="form-control" placeholder="Farm, location or farmer" value="<?= e($q) ?>"></div>
        <div class="col-md-3"><label class="form-label small mb-1">Farmer</label>
            <select name="farmer_id" class="form-select"><?= select_options($lookups['farmers'], 'farmer_id', 'label', (string) $farmerId, 'All farmers') ?></select></div>
        <div class="col-md-2"><label class="form-label small mb-1">Soil type</label>
            <select name="soil_type_id" class="form-select"><?= select_options($lookups['soils'], 'soil_type_id', 'soil_type_name', (string) $soilId, 'All') ?></select></div>
        <div class="col-md-2"><label class="form-label small mb-1">Irrigation</label>
            <select name="irrigation_id" class="form-select"><?= select_options($lookups['irrigations'], 'irrigation_id', 'irrigation_name', (string) $irrId, 'All') ?></select></div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-success flex-fill" type="submit">Filter</button>
            <a href="index.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </div>
</form>

<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr>
            <?= sort_header('Farm', 'farm', $sort, $dir) ?>
            <?= sort_header('Farmer', 'farmer', $sort, $dir) ?>
            <?= sort_header('Location', 'location', $sort, $dir) ?>
            <?= sort_header('Area (ha)', 'area', $sort, $dir) ?>
            <?= sort_header('Soil', 'soil', $sort, $dir) ?>
            <?= sort_header('Irrigation', 'irrigation', $sort, $dir) ?>
            <th class="text-end">Actions</th>
        </tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">No farms found.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['farm_name']) ?></td>
                <td><?= e($r['farmer_name']) ?></td>
                <td><?= e($r['location']) ?></td>
                <td><?= e(fmt_num($r['area_hectares'])) ?></td>
                <td><?= e((string) $r['soil_type_name']) ?></td>
                <td><?= e((string) $r['irrigation_name']) ?></td>
                <td class="text-end text-nowrap">
                    <a href="view.php?id=<?= (int) $r['farm_id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    <a href="edit.php?id=<?= (int) $r['farm_id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    <?php if (is_admin()): ?><?= delete_form((int) $r['farm_id'], 'Delete farm "' . $r['farm_name'] . '"? This cannot be undone.') ?><?php endif; ?>
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
