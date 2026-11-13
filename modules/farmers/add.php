<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$errors = [];
$data   = farmer_defaults();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session token was invalid. Please try again.';
    } else {
        [$errors, $data] = farmer_validate($_POST, null);
        if (!$errors) {
            try {
                $stmt = pdo()->prepare(
                    'INSERT INTO farmer (first_name, last_name, contact_number, address, date_registered)
                     VALUES (:fn, :ln, :cn, :ad, :dr)'
                );
                $stmt->execute([
                    ':fn' => $data['first_name'], ':ln' => $data['last_name'],
                    ':cn' => $data['contact_number'], ':ad' => $data['address'],
                    ':dr' => $data['date_registered'],
                ]);
                set_flash('success', 'Farmer added successfully.');
                redirect('modules/farmers/index.php');
            } catch (PDOException $ex) {
                $errors['form'] = save_error_message($ex, 'This contact number is already registered.');
            }
        }
    }
}

$pageTitle   = 'Add Farmer';
$submitLabel = 'Save Farmer';
include __DIR__ . '/../../includes/header.php';
?>
<h1 class="h3 mb-3">Add Farmer</h1>
<?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
<?php if ($errors && !isset($errors['form'])): ?><div class="alert alert-danger">Please fix the highlighted fields.</div><?php endif; ?>
<div class="card shadow-sm"><div class="card-body"><?php include __DIR__ . '/_form.php'; ?></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
