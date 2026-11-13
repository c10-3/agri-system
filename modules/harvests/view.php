<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$id = posted_id();
$stmt = pdo()->prepare(
    "SELECT h.*, p.planting_date, p.farm_id, p.crop_id, fm.farm_name, fm.farmer_id,
            fr.first_name || ' ' || fr.last_name AS farmer_name, c.crop_name, c.variety,
            qg.grade_name, hr.remark_description
       FROM harvest h
       JOIN planting p ON p.planting_id = h.planting_id
       JOIN farm fm ON fm.farm_id = p.farm_id
       JOIN farmer fr ON fr.farmer_id = fm.farmer_id
       JOIN crop c ON c.crop_id = p.crop_id
       LEFT JOIN quality_grade qg ON qg.grade_id = h.grade_id
       JOIN harvest_remark hr ON hr.remark_id = h.remark_id
      WHERE h.harvest_id = :id"
);
$stmt->execute([':id' => $id ?? 0]);
$h = $stmt->fetch();
if (!$h) {
    set_flash('danger', 'Harvest record not found.');
    redirect('modules/harvests/index.php');
}
$value = $h['selling_price_per_kg'] === null ? null : (float) $h['quantity_kg'] * (float) $h['selling_price_per_kg'];

$pageTitle = 'View Harvest';
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Harvest — <?= e(crop_label($h)) ?></h1>
    <div class="d-flex gap-2">
        <a href="edit.php?id=<?= (int) $id ?>" class="btn btn-outline-primary btn-sm">Edit</a>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to list</a>
    </div>
</div>
<div class="card shadow-sm"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Farm</dt><dd class="col-sm-9"><a href="../farms/view.php?id=<?= (int) $h['farm_id'] ?>"><?= e($h['farm_name']) ?></a></dd>
        <dt class="col-sm-3">Farmer</dt><dd class="col-sm-9"><a href="../farmers/view.php?id=<?= (int) $h['farmer_id'] ?>"><?= e($h['farmer_name']) ?></a></dd>
        <dt class="col-sm-3">Crop</dt><dd class="col-sm-9"><a href="../crops/view.php?id=<?= (int) $h['crop_id'] ?>"><?= e(crop_label($h)) ?></a></dd>
        <dt class="col-sm-3">Planting</dt><dd class="col-sm-9"><a href="../plantings/view.php?id=<?= (int) $h['planting_id'] ?>">Planted <?= e(fmt_date($h['planting_date'])) ?></a></dd>
        <dt class="col-sm-3">Harvest date</dt><dd class="col-sm-9"><?= e(fmt_date($h['harvest_date'])) ?></dd>
        <dt class="col-sm-3">Quantity</dt><dd class="col-sm-9"><?= e(fmt_num($h['quantity_kg'])) ?> kg</dd>
        <dt class="col-sm-3">Selling price</dt><dd class="col-sm-9"><?= e(fmt_money($h['selling_price_per_kg'])) ?> per kg</dd>
        <dt class="col-sm-3">Estimated value</dt><dd class="col-sm-9 fw-semibold"><?= e(fmt_money($value)) ?></dd>
        <dt class="col-sm-3">Quality grade</dt><dd class="col-sm-9"><?= e((string) $h['grade_name']) ?></dd>
        <dt class="col-sm-3">Remark</dt><dd class="col-sm-9"><?= e($h['remark_description']) ?></dd>
        <dt class="col-sm-3">Created</dt><dd class="col-sm-9"><?= e(fmt_date($h['created_at'])) ?></dd>
        <dt class="col-sm-3">Last updated</dt><dd class="col-sm-9"><?= e(fmt_date($h['updated_at'])) ?></dd>
    </dl>
</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
