<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$editId   = posted_id();
$planting = $editId ? planting_find($editId) : null;
if (!$planting) {
    set_flash('danger', 'Planting record not found.');
    redirect('modules/plantings/index.php');
}

$lookups = planting_lookups();
$errors  = [];
$data    = [
    'farm_id' => (string) $planting['farm_id'], 'crop_id' => (string) $planting['crop_id'], 'status_id' => (string) $planting['status_id'],
    'planting_date' => (string) $planting['planting_date'], 'area_planted' => (string) $planting['area_planted'],
    'seed_quantity' => (string) $planting['seed_quantity'], 'expected_harvest_date' => (string) $planting['expected_harvest_date'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session token was invalid. Please try again.';
    } else {
        [$errors, $data] = planting_validate($_POST, $editId);
        if (!$errors) {
            try {
                $stmt = pdo()->prepare(
                    'UPDATE planting SET farm_id = :fa, crop_id = :cr, status_id = :st, planting_date = :pd,
                            area_planted = :ap, seed_quantity = :sq, expected_harvest_date = :eh WHERE planting_id = :id'
                );
                $stmt->execute(planting_params($data) + [':id' => $editId]);
                set_flash('success', 'Planting record updated successfully.');
                redirect('modules/plantings/index.php');
            } catch (PDOException $ex) {
                $errors['form'] = save_error_message($ex, 'This planting already exists.');
            }
        }
    }
}

$pageTitle   = 'Edit Planting';
$submitLabel = 'Update Planting';
include __DIR__ . '/../../includes/header.php';
?>
<h1 class="h3 mb-3">Edit Planting</h1>
<?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
<?php if ($errors && !isset($errors['form'])): ?><div class="alert alert-danger">Please fix the highlighted fields.</div><?php endif; ?>
<div class="card shadow-sm"><div class="card-body"><?php include __DIR__ . '/_form.php'; ?></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
