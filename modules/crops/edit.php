<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$editId = posted_id();
$crop   = $editId ? crop_find($editId) : null;
if (!$crop) {
    set_flash('danger', 'Crop not found.');
    redirect('modules/crops/index.php');
}

$lookups = crop_lookups();
$errors  = [];
$data    = [
    'crop_name' => $crop['crop_name'], 'variety' => (string) $crop['variety'], 'category_id' => (string) $crop['category_id'],
    'season_id' => (string) $crop['season_id'], 'growing_period_days' => (string) $crop['growing_period_days'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session token was invalid. Please try again.';
    } else {
        [$errors, $data] = crop_validate($_POST, $editId);
        if (!$errors) {
            try {
                $stmt = pdo()->prepare(
                    'UPDATE crop SET category_id = :ca, season_id = :se, crop_name = :nm, variety = :va,
                            growing_period_days = :gp WHERE crop_id = :id'
                );
                $stmt->execute([
                    ':ca' => (int) $data['category_id'], ':se' => (int) $data['season_id'], ':nm' => $data['crop_name'],
                    ':va' => $data['variety'] !== '' ? $data['variety'] : null, ':gp' => (int) $data['growing_period_days'], ':id' => $editId,
                ]);
                set_flash('success', 'Crop updated successfully.');
                redirect('modules/crops/index.php');
            } catch (PDOException $ex) {
                $errors['form'] = save_error_message($ex, 'This crop and variety already exists.');
            }
        }
    }
}

$pageTitle   = 'Edit Crop';
$submitLabel = 'Update Crop';
include __DIR__ . '/../../includes/header.php';
?>
<h1 class="h3 mb-3">Edit Crop</h1>
<?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
<?php if ($errors && !isset($errors['form'])): ?><div class="alert alert-danger">Please fix the highlighted fields.</div><?php endif; ?>
<div class="card shadow-sm"><div class="card-body"><?php include __DIR__ . '/_form.php'; ?></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
