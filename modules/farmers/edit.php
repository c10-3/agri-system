<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$editId = posted_id();
$farmer = $editId ? farmer_find($editId) : null;
if (!$farmer) {
    set_flash('danger', 'Farmer not found.');
    redirect('modules/farmers/index.php');
}

$errors = [];
$data   = [
    'first_name' => $farmer['first_name'], 'last_name' => $farmer['last_name'],
    'contact_number' => (string) $farmer['contact_number'], 'address' => (string) $farmer['address'],
    'date_registered' => (string) $farmer['date_registered'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session token was invalid. Please try again.';
    } else {
        [$errors, $data] = farmer_validate($_POST, $editId);
        if (!$errors) {
            try {
                $stmt = pdo()->prepare(
                    'UPDATE farmer SET first_name = :fn, last_name = :ln, contact_number = :cn,
                            address = :ad, date_registered = :dr WHERE farmer_id = :id'
                );
                $stmt->execute([
                    ':fn' => $data['first_name'], ':ln' => $data['last_name'],
                    ':cn' => $data['contact_number'], ':ad' => $data['address'],
                    ':dr' => $data['date_registered'], ':id' => $editId,
                ]);
                set_flash('success', 'Farmer updated successfully.');
                redirect('modules/farmers/index.php');
            } catch (PDOException $ex) {
                $errors['form'] = save_error_message($ex, 'This contact number is already registered.');
            }
        }
    }
}

$pageTitle   = 'Edit Farmer';
$submitLabel = 'Update Farmer';
include __DIR__ . '/../../includes/header.php';
?>
<h1 class="h3 mb-3">Edit Farmer</h1>
<?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
<?php if ($errors && !isset($errors['form'])): ?><div class="alert alert-danger">Please fix the highlighted fields.</div><?php endif; ?>
<div class="card shadow-sm"><div class="card-body"><?php include __DIR__ . '/_form.php'; ?></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
