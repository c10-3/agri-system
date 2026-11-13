<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$pdo = auth_db();

// First account ever created becomes the Administrator; after that only admins can add users.
$bootstrap = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0;
if (!$bootstrap) {
    require_role([ROLE_ADMIN]);
}

$roles = $pdo->query('SELECT role_id, role_name FROM role ORDER BY role_id')->fetchAll();
$errors = [];
$success = '';
$old = ['username' => '', 'full_name' => '', 'role_id' => ''];

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
            $success = 'User "' . $old['username'] . '" created successfully.';
            $old = ['username' => '', 'full_name' => '', 'role_id' => ''];
        } catch (PDOException $e) {
            error_log('Register failed: ' . $e->getMessage());
            $errors[] = 'Could not create the user. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register User - Agriculture Information System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5" style="max-width: 480px;">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h4 class="mb-1"><?= $bootstrap ? 'Create Administrator Account' : 'Register New User' ?></h4>
        <p class="text-muted"><?= $bootstrap ? 'No users exist yet. This first account will be the Administrator.' : 'Administrators only.' ?></p>

        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($errors): ?>
          <div class="alert alert-danger"><ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
          </ul></div>
        <?php endif; ?>

        <form method="post" novalidate>
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label" for="full_name">Full name</label>
            <input class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($old['full_name']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="username">Username</label>
            <input class="form-control" id="username" name="username" value="<?= htmlspecialchars($old['username']) ?>" required>
          </div>
          <?php if (!$bootstrap): ?>
          <div class="mb-3">
            <label class="form-label" for="role_id">Role</label>
            <select class="form-select" id="role_id" name="role_id" required>
              <option value="">-- Select role --</option>
              <?php foreach ($roles as $r): ?>
                <option value="<?= (int) $r['role_id'] ?>" <?= (string) $old['role_id'] === (string) $r['role_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($r['role_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="confirm_password">Confirm password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
          </div>
          <button class="btn btn-success w-100" type="submit">Create account</button>
        </form>
        <?php if (!$bootstrap): ?>
          <a href="dashboard.php" class="d-block text-center mt-3">Back to Dashboard</a>
        <?php else: ?>
          <a href="login.php" class="d-block text-center mt-3">Back to Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
