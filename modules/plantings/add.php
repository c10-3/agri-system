<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$errors  = [];
$data    = planting_defaults();
$lookups = planting_lookups();
if (!empty($lookups['statuses'])) {
    $data['status_id'] = (string) $lookups['statuses'][0]['status_id'];   // first status = "Planted"
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session token was invalid. Please try again.';
    } else {
        [$errors, $data] = planting_validate($_POST, null);
        if (!$errors) {
            try {
                $stmt = pdo()->prepare(
                    'INSERT INTO planting (farm_id, crop_id, status_id, planting_date, area_planted, seed_quantity, expected_harvest_date)
                     VALUES (:fa, :cr, :st, :pd, :ap, :sq, :eh)'
                );
                $stmt->execute(planting_params($data));
                set_flash('success', 'Planting record added successfully.');
                redirect('modules/plantings/index.php');
            } catch (PDOException $ex) {
                $errors['form'] = save_error_message($ex, 'This planting already exists.');
            }
        }
    }
}

$pageTitle   = 'Add Planting';
$submitLabel = 'Save Planting';
include __DIR__ . '/../../includes/header.php';
?>
<h1 class="h3 mb-3">Add Planting</h1>
<?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
<?php if ($errors && !isset($errors['form'])): ?><div class="alert alert-danger">Please fix the highlighted fields.</div><?php endif; ?>
<div class="card shadow-sm"><div class="card-body"><?php include __DIR__ . '/_form.php'; ?></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
