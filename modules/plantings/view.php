<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$id = posted_id();
$stmt = pdo()->prepare(
    "SELECT p.*, fm.farm_name, fm.farmer_id, fr.first_name || ' ' || fr.last_name AS farmer_name,
            c.crop_name, c.variety, ps.status_name
       FROM planting p
       JOIN farm fm ON fm.farm_id = p.farm_id
       JOIN farmer fr ON fr.farmer_id = fm.farmer_id
       JOIN crop c ON c.crop_id = p.crop_id
       JOIN planting_status ps ON ps.status_id = p.status_id
      WHERE p.planting_id = :id"
);
$stmt->execute([':id' => $id ?? 0]);
$p = $stmt->fetch();
if (!$p) {
    set_flash('danger', 'Planting record not found.');
    redirect('modules/plantings/index.php');
}

$stmt = pdo()->prepare(
    'SELECT h.harvest_id, h.harvest_date, h.quantity_kg, h.selling_price_per_kg, qg.grade_name, hr.remark_description
       FROM harvest h
       LEFT JOIN quality_grade qg ON qg.grade_id = h.grade_id
       JOIN harvest_remark hr ON hr.remark_id = h.remark_id
      WHERE h.planting_id = :id ORDER BY h.harvest_date DESC'
);
$stmt->execute([':id' => $id]);
$harvests = $stmt->fetchAll();

$pageTitle = 'View Planting';
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e($p['farm_name'] . ' — ' . crop_label($p)) ?></h1>
    <div class="d-flex gap-2">
        <a href="edit.php?id=<?= (int) $id ?>" class="btn btn-outline-primary btn-sm">Edit</a>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to list</a>
    </div>
</div>
<div class="card shadow-sm mb-4"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Farm</dt><dd class="col-sm-9"><a href="../farms/view.php?id=<?= (int) $p['farm_id'] ?>"><?= e($p['farm_name']) ?></a></dd>
        <dt class="col-sm-3">Farmer</dt><dd class="col-sm-9"><a href="../farmers/view.php?id=<?= (int) $p['farmer_id'] ?>"><?= e($p['farmer_name']) ?></a></dd>
        <dt class="col-sm-3">Crop</dt><dd class="col-sm-9"><a href="../crops/view.php?id=<?= (int) $p['crop_id'] ?>"><?= e(crop_label($p)) ?></a></dd>
        <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><?= e($p['status_name']) ?></dd>
        <dt class="col-sm-3">Planting date</dt><dd class="col-sm-9"><?= e(fmt_date($p['planting_date'])) ?></dd>
        <dt class="col-sm-3">Expected harvest</dt><dd class="col-sm-9"><?= e(fmt_date($p['expected_harvest_date'])) ?></dd>
        <dt class="col-sm-3">Area planted</dt><dd class="col-sm-9"><?= e(fmt_num($p['area_planted'])) ?> ha</dd>
        <dt class="col-sm-3">Seed quantity</dt><dd class="col-sm-9"><?= e(fmt_num($p['seed_quantity'])) ?></dd>
        <dt class="col-sm-3">Last updated</dt><dd class="col-sm-9"><?= e(fmt_date($p['updated_at'])) ?></dd>
    </dl>
</div></div>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h5 mb-0">Harvests</h2>
    <a href="../harvests/add.php?planting_id=<?= (int) $id ?>" class="btn btn-success btn-sm">+ Record harvest</a>
</div>
<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Date</th><th>Quantity (kg)</th><th>Price / kg</th><th>Grade</th><th>Remark</th></tr></thead>
        <tbody>
        <?php if (!$harvests): ?><tr><td colspan="5" class="text-center text-muted py-4">No harvests recorded for this planting.</td></tr><?php endif; ?>
        <?php foreach ($harvests as $h): ?>
            <tr>
                <td><a href="../harvests/view.php?id=<?= (int) $h['harvest_id'] ?>"><?= e(fmt_date($h['harvest_date'])) ?></a></td>
                <td><?= e(fmt_num($h['quantity_kg'])) ?></td>
                <td><?= e(fmt_money($h['selling_price_per_kg'])) ?></td>
                <td><?= e((string) $h['grade_name']) ?></td>
                <td><?= e($h['remark_description']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
