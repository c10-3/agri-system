<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_role([ROLE_ADMIN]);

$rows = db_rows(
    'SELECT u.user_id, u.username, u.full_name, u.is_active, u.created_at, r.role_name
       FROM users u
       JOIN role r ON r.role_id = u.role_id
      ORDER BY u.full_name'
);

$pageTitle = 'Users';
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Users</h1>
    <a href="register.php" class="btn btn-outline-primary px-4 fw-semibold">+ Add User</a>
</div>

<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr>
            <th>Full name</th>
            <th>Username</th>
            <th>Role</th>
            <th>Status</th>
            <th>Created</th>
        </tr></thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No users found.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['full_name']) ?></td>
                <td><?= e($r['username']) ?></td>
                <td><span class="badge text-bg-secondary"><?= e($r['role_name']) ?></span></td>
                <td>
                    <?php if ($r['is_active']): ?>
                        <span class="badge text-bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge text-bg-danger">Inactive</span>
                    <?php endif; ?>
                </td>
                <td><?= e(fmt_date($r['created_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>
<div class="mt-3"><small class="text-muted"><?= count($rows) ?> user(s)</small></div>
<?php include __DIR__ . '/includes/footer.php'; ?>