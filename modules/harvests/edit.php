<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$editId  = posted_id();
$harvest = $editId ? harvest_find($editId) : null;
if (!$harvest) {
    set_flash('danger', 'Harvest record not found.');
    redirect('modules/harvests/index.php');
}

$lookups = harvest_lookups();
$errors  = [];
$data    = [
    'planting_id' => (string) $harvest['planting_id'], 'grade_id' => (string) $harvest['grade_id'], 'remark_id' => (string) $harvest['remark_id'],
    'harvest_date' => (string) $harvest['harvest_date'], 'quantity_kg' => (string) $harvest['quantity_kg'],
    'selling_price_per_kg' => (string) $harvest['selling_price_per_kg'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session token was invalid. Please try again.';
    } else {
        [$errors, $data] = harvest_validate($_POST, $editId);
        if (!$errors) {
            try {
                $stmt = pdo()->prepare(
                    'UPDATE harvest SET planting_id = :pl, grade_id = :gr, remark_id = :re, harvest_date = :hd,
                            quantity_kg = :qt, selling_price_per_kg = :pr WHERE harvest_id = :id'
                );
                $stmt->execute(harvest_params($data) + [':id' => $editId]);
                set_flash('success', 'Harvest updated successfully.');
                redirect('modules/harvests/index.php');
            } catch (PDOException $ex) {
                $errors['form'] = save_error_message($ex, 'This harvest already exists.');
            }
        }
    }
}

$pageTitle   = 'Edit Harvest';
$submitLabel = 'Update Harvest';
include __DIR__ . '/../../includes/header.php';
?>
<h1 class="h3 mb-3">Edit Harvest</h1>
<?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
<?php if ($errors && !isset($errors['form'])): ?><div class="alert alert-danger">Please fix the highlighted fields.</div><?php endif; ?>
<div class="card shadow-sm"><div class="card-body"><?php include __DIR__ . '/_form.php'; ?></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
