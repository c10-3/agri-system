<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$errors  = [];
$data    = crop_defaults();
$lookups = crop_lookups();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session token was invalid. Please try again.';
    } else {
        [$errors, $data] = crop_validate($_POST, null);
        if (!$errors) {
            try {
                $stmt = pdo()->prepare(
                    'INSERT INTO crop (category_id, season_id, crop_name, variety, growing_period_days)
                     VALUES (:ca, :se, :nm, :va, :gp)'
                );
                $stmt->execute([
                    ':ca' => (int) $data['category_id'], ':se' => (int) $data['season_id'], ':nm' => $data['crop_name'],
                    ':va' => $data['variety'] !== '' ? $data['variety'] : null, ':gp' => (int) $data['growing_period_days'],
                ]);
                set_flash('success', 'Crop added successfully.');
                redirect('modules/crops/index.php');
            } catch (PDOException $ex) {
                $errors['form'] = save_error_message($ex, 'This crop and variety already exists.');
            }
        }
    }
}

$pageTitle   = 'Add Crop';
$submitLabel = 'Save Crop';
include __DIR__ . '/../../includes/header.php';
?>
<h1 class="h3 mb-3">Add Crop</h1>
<?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
<?php if ($errors && !isset($errors['form'])): ?><div class="alert alert-danger">Please fix the highlighted fields.</div><?php endif; ?>
<div class="card shadow-sm"><div class="card-body"><?php include __DIR__ . '/_form.php'; ?></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
