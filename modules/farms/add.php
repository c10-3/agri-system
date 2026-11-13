<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$errors  = [];
$data    = farm_defaults();
$lookups = farm_lookups();
if (isset($_GET['farmer_id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $data['farmer_id'] = (string) (get_int('farmer_id') ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session token was invalid. Please try again.';
    } else {
        [$errors, $data] = farm_validate($_POST, null);
        if (!$errors) {
            try {
                $stmt = pdo()->prepare(
                    'INSERT INTO farm (farmer_id, soil_type_id, irrigation_id, farm_name, location, area_hectares)
                     VALUES (:fa, :so, :ir, :nm, :lo, :ar)'
                );
                $stmt->execute([
                    ':fa' => (int) $data['farmer_id'], ':so' => (int) $data['soil_type_id'], ':ir' => (int) $data['irrigation_id'],
                    ':nm' => $data['farm_name'], ':lo' => $data['location'], ':ar' => $data['area_hectares'],
                ]);
                set_flash('success', 'Farm added successfully.');
                redirect('modules/farms/index.php');
            } catch (PDOException $ex) {
                $errors['form'] = save_error_message($ex, 'This farmer already has a farm with that name.');
            }
        }
    }
}

$pageTitle   = 'Add Farm';
$submitLabel = 'Save Farm';
include __DIR__ . '/../../includes/header.php';
?>
<h1 class="h3 mb-3">Add Farm</h1>
<?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
<?php if ($errors && !isset($errors['form'])): ?><div class="alert alert-danger">Please fix the highlighted fields.</div><?php endif; ?>
<div class="card shadow-sm"><div class="card-body"><?php include __DIR__ . '/_form.php'; ?></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
