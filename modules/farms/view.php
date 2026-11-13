<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$id = posted_id();
$stmt = pdo()->prepare(
    "SELECT f.*, fr.first_name || ' ' || fr.last_name AS farmer_name, s.soil_type_name, i.irrigation_name
       FROM farm f
       JOIN farmer fr ON fr.farmer_id = f.farmer_id
       LEFT JOIN soil_type s ON s.soil_type_id = f.soil_type_id
       LEFT JOIN irrigation_type i ON i.irrigation_id = f.irrigation_id
      WHERE f.farm_id = :id"
);
$stmt->execute([':id' => $id ?? 0]);
$farm = $stmt->fetch();
if (!$farm) {
    set_flash('danger', 'Farm not found.');
    redirect('modules/farms/index.php');
}

$stmt = pdo()->prepare(
    'SELECT p.planting_id, p.planting_date, p.area_planted, p.expected_harvest_date, c.crop_name, c.variety, ps.status_name
       FROM planting p
       JOIN crop c ON c.crop_id = p.crop_id
       JOIN planting_status ps ON ps.status_id = p.status_id
      WHERE p.farm_id = :id ORDER BY p.planting_date DESC'
);
$stmt->execute([':id' => $id]);
$plantings = $stmt->fetchAll();

$pageTitle = 'View Farm';
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e($farm['farm_name']) ?></h1>
    <div class="d-flex gap-2">
        <a href="edit.php?id=<?= (int) $id ?>" class="btn btn-outline-primary btn-sm">Edit</a>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to list</a>
    </div>
</div>
<div class="card shadow-sm mb-4"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Farmer</dt><dd class="col-sm-9"><a href="../farmers/view.php?id=<?= (int) $farm['farmer_id'] ?>"><?= e($farm['farmer_name']) ?></a></dd>
        <dt class="col-sm-3">Location</dt><dd class="col-sm-9"><?= e($farm['location']) ?></dd>
        <dt class="col-sm-3">Area</dt><dd class="col-sm-9"><?= e(fmt_num($farm['area_hectares'])) ?> hectares</dd>
        <dt class="col-sm-3">Soil type</dt><dd class="col-sm-9"><?= e((string) $farm['soil_type_name']) ?></dd>
        <dt class="col-sm-3">Irrigation</dt><dd class="col-sm-9"><?= e((string) $farm['irrigation_name']) ?></dd>
        <dt class="col-sm-3">Created</dt><dd class="col-sm-9"><?= e(fmt_date($farm['created_at'])) ?></dd>
        <dt class="col-sm-3">Last updated</dt><dd class="col-sm-9"><?= e(fmt_date($farm['updated_at'])) ?></dd>
    </dl>
</div></div>
<h2 class="h5">Planting records</h2>
<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Crop</th><th>Planted</th><th>Area (ha)</th><th>Expected harvest</th><th>Status</th></tr></thead>
        <tbody>
        <?php if (!$plantings): ?><tr><td colspan="5" class="text-center text-muted py-4">No plantings on this farm yet.</td></tr><?php endif; ?>
        <?php foreach ($plantings as $p): ?>
            <tr>
                <td><a href="../plantings/view.php?id=<?= (int) $p['planting_id'] ?>"><?= e($p['crop_name'] . ($p['variety'] ? ' (' . $p['variety'] . ')' : '')) ?></a></td>
                <td><?= e(fmt_date($p['planting_date'])) ?></td>
                <td><?= e(fmt_num($p['area_planted'])) ?></td>
                <td><?= e(fmt_date($p['expected_harvest_date'])) ?></td>
                <td><?= e($p['status_name']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
