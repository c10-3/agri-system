<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$id     = posted_id();
$farmer = $id ? farmer_find($id) : null;
if (!$farmer) {
    set_flash('danger', 'Farmer not found.');
    redirect('modules/farmers/index.php');
}

$stmt = pdo()->prepare(
    'SELECT f.farm_id, f.farm_name, f.location, f.area_hectares, s.soil_type_name, i.irrigation_name
       FROM farm f
       LEFT JOIN soil_type s ON s.soil_type_id = f.soil_type_id
       LEFT JOIN irrigation_type i ON i.irrigation_id = f.irrigation_id
      WHERE f.farmer_id = :id ORDER BY f.farm_name'
);
$stmt->execute([':id' => $id]);
$farms = $stmt->fetchAll();

$pageTitle = 'View Farmer';
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e($farmer['first_name'] . ' ' . $farmer['last_name']) ?></h1>
    <div class="d-flex gap-2">
        <a href="edit.php?id=<?= (int) $id ?>" class="btn btn-outline-primary btn-sm">Edit</a>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to list</a>
    </div>
</div>

<div class="card shadow-sm mb-4"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Contact number</dt><dd class="col-sm-9"><?= e((string) $farmer['contact_number']) ?></dd>
        <dt class="col-sm-3">Address</dt><dd class="col-sm-9"><?= e((string) $farmer['address']) ?></dd>
        <dt class="col-sm-3">Date registered</dt><dd class="col-sm-9"><?= e(fmt_date($farmer['date_registered'])) ?></dd>
        <dt class="col-sm-3">Created</dt><dd class="col-sm-9"><?= e(fmt_date($farmer['created_at'])) ?></dd>
        <dt class="col-sm-3">Last updated</dt><dd class="col-sm-9"><?= e(fmt_date($farmer['updated_at'])) ?></dd>
    </dl>
</div></div>

<h2 class="h5">Farms</h2>
<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Farm</th><th>Location</th><th>Area (ha)</th><th>Soil</th><th>Irrigation</th></tr></thead>
        <tbody>
        <?php if (!$farms): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">This farmer has no farms yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($farms as $f): ?>
            <tr>
                <td><a href="../farms/view.php?id=<?= (int) $f['farm_id'] ?>"><?= e($f['farm_name']) ?></a></td>
                <td><?= e($f['location']) ?></td>
                <td><?= e(fmt_num($f['area_hectares'])) ?></td>
                <td><?= e((string) $f['soil_type_name']) ?></td>
                <td><?= e((string) $f['irrigation_name']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
