<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$editId = posted_id();
$farm   = $editId ? farm_find($editId) : null;
if (!$farm) {
    set_flash('danger', 'Farm not found.');
    redirect('modules/farms/index.php');
}

$lookups = farm_lookups();
$errors  = [];
$data    = [
    'farmer_id' => (string) $farm['farmer_id'], 'farm_name' => $farm['farm_name'], 'location' => $farm['location'],
    'area_hectares' => (string) $farm['area_hectares'], 'soil_type_id' => (string) $farm['soil_type_id'],
    'irrigation_id' => (string) $farm['irrigation_id'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session token was invalid. Please try again.';
    } else {
        [$errors, $data] = farm_validate($_POST, $editId);
        if (!$errors) {
            try {
                $stmt = pdo()->prepare(
                    'UPDATE farm SET farmer_id = :fa, soil_type_id = :so, irrigation_id = :ir, farm_name = :nm,
                            location = :lo, area_hectares = :ar WHERE farm_id = :id'
                );
                $stmt->execute([
                    ':fa' => (int) $data['farmer_id'], ':so' => (int) $data['soil_type_id'], ':ir' => (int) $data['irrigation_id'],
                    ':nm' => $data['farm_name'], ':lo' => $data['location'], ':ar' => $data['area_hectares'], ':id' => $editId,
                ]);
                set_flash('success', 'Farm updated successfully.');
                redirect('modules/farms/index.php');
            } catch (PDOException $ex) {
                $errors['form'] = save_error_message($ex, 'This farmer already has a farm with that name.');
            }
        }
    }
}

$pageTitle   = 'Edit Farm';
$submitLabel = 'Update Farm';
include __DIR__ . '/../../includes/header.php';
?>
<h1 class="h3 mb-3">Edit Farm</h1>
<?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
<?php if ($errors && !isset($errors['form'])): ?><div class="alert alert-danger">Please fix the highlighted fields.</div><?php endif; ?>
<div class="card shadow-sm"><div class="card-body"><?php include __DIR__ . '/_form.php'; ?></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
