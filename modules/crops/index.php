<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_login();

$q          = get_str('q');
$categoryId = get_int('category_id');
$seasonId   = get_int('season_id');

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(c.crop_name ILIKE :q1 OR c.variety ILIKE :q2)';
    $like = like_param($q);
    $params += [':q1' => $like, ':q2' => $like];
}
if ($categoryId) { $where[] = 'c.category_id = :cid'; $params[':cid'] = $categoryId; }
if ($seasonId)   { $where[] = 'c.season_id = :sid';   $params[':sid'] = $seasonId; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

[$sort, $dir, $order] = sort_params([
    'name'     => 'c.crop_name',
    'variety'  => 'c.variety',
    'category' => 'cc.category_name',
    'season'   => 's.season_name',
    'days'     => 'c.growing_period_days',
], 'name');
$order .= ', c.crop_id';

[$rows, $total, $page, $pages] = paged_query(
    'c.crop_id, c.crop_name, c.variety, c.growing_period_days, cc.category_name, s.season_name',
    'crop c LEFT JOIN crop_category cc ON cc.category_id = c.category_id LEFT JOIN season s ON s.season_id = c.season_id',
    $whereSql, $params, $order
);
$lookups = crop_lookups();

$pageTitle = 'Crops';
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Crops</h1>
    <a href="add.php" class="btn btn-success">+ Add Crop</a>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label small mb-1">Search</label>
            <input type="text" name="q" class="form-control" placeholder="Crop name or variety" value="<?= e($q) ?>"></div>
        <div class="col-md-3"><label class="form-label small mb-1">Category</label>
            <select name="category_id" class="form-select"><?= select_options($lookups['categories'], 'category_id', 'category_name', (string) $categoryId, 'All categories') ?></select></div>
        <div class="col-md-3"><label class="form-label small mb-1">Season</label>
            <select name="season_id" class="form-select"><?= select_options($lookups['seasons'], 'season_id', 'season_name', (string) $seasonId, 'All seasons') ?></select></div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-success flex-fill" type="submit">Filter</button>
            <a href="index.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </div>
</form>

<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr>
            <?= sort_header('Crop', 'name', $sort, $dir) ?>
            <?= sort_header('Variety', 'variety', $sort, $dir) ?>
            <?= sort_header('Category', 'category', $sort, $dir) ?>
            <?= sort_header('Season', 'season', $sort, $dir) ?>
            <?= sort_header('Days to grow', 'days', $sort, $dir) ?>
            <th class="text-end">Actions</th>
        </tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">No crops found.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['crop_name']) ?></td>
                <td><?= e((string) $r['variety']) ?></td>
                <td><?= e((string) $r['category_name']) ?></td>
                <td><?= e((string) $r['season_name']) ?></td>
                <td><?= e((string) $r['growing_period_days']) ?></td>
                <td class="text-end text-nowrap">
                    <a href="view.php?id=<?= (int) $r['crop_id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    <a href="edit.php?id=<?= (int) $r['crop_id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    <?php if (is_admin()): ?><?= delete_form((int) $r['crop_id'], 'Delete crop "' . crop_label($r) . '"? This cannot be undone.') ?><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mt-3">
    <small class="text-muted"><?= e(record_range($total, $page)) ?></small>
    <?php render_pager($page, $pages); ?>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
