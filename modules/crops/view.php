<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$id = posted_id();
$stmt = pdo()->prepare(
    'SELECT c.*, cc.category_name, s.season_name
       FROM crop c
       LEFT JOIN crop_category cc ON cc.category_id = c.category_id
       LEFT JOIN season s ON s.season_id = c.season_id
      WHERE c.crop_id = :id'
);
$stmt->execute([':id' => $id ?? 0]);
$crop = $stmt->fetch();
if (!$crop) {
    set_flash('danger', 'Crop not found.');
    redirect('modules/crops/index.php');
}

$stmt = pdo()->prepare(
    "SELECT p.planting_id, p.planting_date, p.area_planted, fm.farm_name, fr.first_name || ' ' || fr.last_name AS farmer_name, ps.status_name
       FROM planting p
       JOIN farm fm ON fm.farm_id = p.farm_id
       JOIN farmer fr ON fr.farmer_id = fm.farmer_id
       JOIN planting_status ps ON ps.status_id = p.status_id
      WHERE p.crop_id = :id ORDER BY p.planting_date DESC"
);
$stmt->execute([':id' => $id]);
$plantings = $stmt->fetchAll();

$pageTitle = 'View Crop';
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e(crop_label($crop)) ?></h1>
    <div class="d-flex gap-2">
        <a href="edit.php?id=<?= (int) $id ?>" class="btn btn-outline-primary btn-sm">Edit</a>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to list</a>
    </div>
</div>
<div class="card shadow-sm mb-4"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Category</dt><dd class="col-sm-9"><?= e((string) $crop['category_name']) ?></dd>
        <dt class="col-sm-3">Season</dt><dd class="col-sm-9"><?= e((string) $crop['season_name']) ?></dd>
        <dt class="col-sm-3">Growing period</dt><dd class="col-sm-9"><?= e((string) $crop['growing_period_days']) ?> days</dd>
        <dt class="col-sm-3">Created</dt><dd class="col-sm-9"><?= e(fmt_date($crop['created_at'])) ?></dd>
        <dt class="col-sm-3">Last updated</dt><dd class="col-sm-9"><?= e(fmt_date($crop['updated_at'])) ?></dd>
    </dl>
</div></div>
<h2 class="h5">Where this crop is planted</h2>
<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Farm</th><th>Farmer</th><th>Planted</th><th>Area (ha)</th><th>Status</th></tr></thead>
        <tbody>
        <?php if (!$plantings): ?><tr><td colspan="5" class="text-center text-muted py-4">This crop has not been planted yet.</td></tr><?php endif; ?>
        <?php foreach ($plantings as $p): ?>
            <tr>
                <td><a href="../plantings/view.php?id=<?= (int) $p['planting_id'] ?>"><?= e($p['farm_name']) ?></a></td>
                <td><?= e($p['farmer_name']) ?></td>
                <td><?= e(fmt_date($p['planting_date'])) ?></td>
                <td><?= e(fmt_num($p['area_planted'])) ?></td>
                <td><?= e($p['status_name']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
