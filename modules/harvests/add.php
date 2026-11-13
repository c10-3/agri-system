<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$errors  = [];
$data    = harvest_defaults();
$lookups = harvest_lookups();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && get_int('planting_id')) {
    $data['planting_id'] = (string) get_int('planting_id');   // coming from a planting's page
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session token was invalid. Please try again.';
    } else {
        [$errors, $data] = harvest_validate($_POST, null);
        if (!$errors) {
            try {
                $stmt = pdo()->prepare(
                    'INSERT INTO harvest (planting_id, grade_id, remark_id, harvest_date, quantity_kg, selling_price_per_kg)
                     VALUES (:pl, :gr, :re, :hd, :qt, :pr)'
                );
                $stmt->execute(harvest_params($data));
                set_flash('success', 'Harvest recorded successfully.');
                redirect('modules/harvests/index.php');
            } catch (PDOException $ex) {
                $errors['form'] = save_error_message($ex, 'This harvest already exists.');
            }
        }
    }
}

$pageTitle   = 'Add Harvest';
$submitLabel = 'Save Harvest';
include __DIR__ . '/../../includes/header.php';
?>
<h1 class="h3 mb-3">Add Harvest</h1>
<?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
<?php if ($errors && !isset($errors['form'])): ?><div class="alert alert-danger">Please fix the highlighted fields.</div><?php endif; ?>
<div class="card shadow-sm"><div class="card-body"><?php include __DIR__ . '/_form.php'; ?></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
