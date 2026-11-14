<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$pdo = auth_db();

// First account ever created becomes the Administrator; after that only admins can add users.
$bootstrap = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0;
if (!$bootstrap) {
    require_role([ROLE_ADMIN]);
}

$roles  = $pdo->query('SELECT role_id, role_name FROM role ORDER BY role_id')->fetchAll();
$errors = [];
$old    = ['username' => '', 'full_name' => '', 'role_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['username']  = trim($_POST['username'] ?? '');
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['role_id']   = $_POST['role_id'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors[] = 'Invalid request. Please try again.';
    }
    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $old['username'])) {
        $errors[] = 'Username must be 3-50 characters (letters, numbers, . _ -).';
    }
    if ($old['full_name'] === '' || strlen($old['full_name']) > 100) {
        $errors[] = 'Full name is required (max 100 characters).';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    $roleId = null;
    foreach ($roles as $r) {
        if ($bootstrap ? $r['role_name'] === ROLE_ADMIN : (string) $r['role_id'] === (string) $old['role_id']) {
            $roleId = (int) $r['role_id'];
        }
    }
    if ($roleId === null) {
        $errors[] = 'Please choose a valid role.';
    }

    if (!$errors) {
        $dup = $pdo->prepare('SELECT 1 FROM users WHERE username = :u');
        $dup->execute([':u' => $old['username']]);
        if ($dup->fetchColumn()) {
            $errors[] = 'That username is already taken.';
        }
    }

    if (!$errors) {
        try {
            $pdo->prepare(
                'INSERT INTO users (username, password_hash, full_name, role_id, is_active)
                 VALUES (:u, :p, :f, :r, TRUE)'
            )->execute([
                ':u' => $old['username'],
                ':p' => password_hash($password, PASSWORD_DEFAULT),
                ':f' => $old['full_name'],
                ':r' => $roleId,
            ]);
            if ($bootstrap) {
                $_SESSION['flash_error'] = 'Administrator account created. Please log in.';
                header('Location: login.php');
                exit;
            }
            set_flash('success', 'User "' . $old['username'] . '" created successfully.');
            redirect('users.php');
        } catch (PDOException $ex) {
            error_log('Register failed: ' . $ex->getMessage());
            $errors[] = 'Could not create the user. Please try again.';
        }
    }
}

$pageTitle = $bootstrap ? 'Create Administrator' : 'Add User';
include __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-3"><?= $bootstrap ? 'Create Administrator Account' : 'Add User' ?></h1>

<?php if ($bootstrap): ?>
    <div class="alert alert-info">No users exist yet. This first account will be the Administrator.</div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0">
        <?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
    </ul></div>
<?php endif; ?>

<div class="card shadow-sm"><div class="card-body">
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="full_name">Full name <span class="text-danger">*</span></label>
                <input class="form-control" id="full_name" name="full_name" maxlength="100"
                       value="<?= e($old['full_name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="username">Username <span class="text-danger">*</span></label>
                <input class="form-control" id="username" name="username" maxlength="50"
                       value="<?= e($old['username']) ?>" required>
            </div>
            <?php if (!$bootstrap): ?>
            <div class="col-md-6">
                <label class="form-label" for="role_id">Role <span class="text-danger">*</span></label>
                <select class="form-select" id="role_id" name="role_id" required>
                    <option value="">-- Select role --</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= (int) $r['role_id'] ?>" <?= (string) $old['role_id'] === (string) $r['role_id'] ? 'selected' : '' ?>>
                            <?= e($r['role_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6"></div>
            <?php endif; ?>
            <div class="col-md-6">
                <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="form-text">At least 8 characters.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="confirm_password">Confirm password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-success" type="submit">Create Account</button>
            <a href="<?= $bootstrap ? 'login.php' : 'users.php' ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>