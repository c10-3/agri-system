<?php
declare(strict_types=1);
require_once __DIR__ . '/_report_lib.php';

[$from, $to, $dateError] = rpt_dates();

// Optional filter: one farm only
$farmFilter = filter_var($_GET['farm_id'] ?? '', FILTER_VALIDATE_INT);
$farmId     = ($farmFilter === false) ? null : (int) $farmFilter;

$conds  = [];
$params = [];
rpt_date_conditions('p.planting_date', $from, $to, $conds, $params);
if ($farmId !== null) {
    $conds[]          = 'f.farm_id = :farm';
    $params[':farm']  = $farmId;
}
$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$baseSql = "FROM planting p
            JOIN farm f ON f.farm_id = p.farm_id
            JOIN farmer fr ON fr.farmer_id = f.farmer_id
            JOIN crop c ON c.crop_id = p.crop_id
            JOIN crop_category cc ON cc.category_id = c.category_id
            $where";
$groupSql = 'GROUP BY f.farm_id, f.farm_name, fr.last_name, fr.first_name, c.crop_id, c.crop_name, c.variety, cc.category_name';

// Overall totals
$stmt = pdo()->prepare(
    "SELECT COUNT(*) AS plantings,
            COUNT(DISTINCT p.farm_id) AS farms,
            COUNT(DISTINCT p.crop_id) AS crops,
            COALESCE(SUM(p.area_planted), 0) AS area
     FROM planting p JOIN farm f ON f.farm_id = p.farm_id $where"
);
$stmt->execute($params);
$summary = $stmt->fetch();

// Number of result rows (for pagination)
$stmt = pdo()->prepare("SELECT COUNT(*) FROM (SELECT 1 $baseSql $groupSql) g");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$perPage = 15;
$pages   = max(1, (int) ceil($total / $perPage));
$page    = min(rpt_page(), $pages);
$offset  = ($page - 1) * $perPage;

$stmt = pdo()->prepare(
    "SELECT f.farm_name, fr.last_name || ', ' || fr.first_name AS owner,
            c.crop_name, c.variety, cc.category_name,
            COUNT(p.planting_id) AS plantings, SUM(p.area_planted) AS area
     $baseSql $groupSql
     ORDER BY f.farm_name, c.crop_name, c.variety
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$farms = pdo()->query('SELECT farm_id, farm_name FROM farm ORDER BY farm_name')->fetchAll();
$extra = rpt_select('farm_id', 'Farm', array_column($farms, 'farm_name', 'farm_id'),
                    $farmId === null ? '' : (string) $farmId, 'All farms');

report_header('Crops Planted per Farm');
rpt_title('Crops Planted per Farm', rpt_period($from, $to), $dateError);
rpt_filter_form($from, $to, 'planting date', $extra);
rpt_cards([
    ['Farms',        rpt_num($summary['farms'], 0)],
    ['Crops',        rpt_num($summary['crops'], 0)],
    ['Plantings',    rpt_num($summary['plantings'], 0)],
    ['Area planted', rpt_num($summary['area']) . ' ha'],
]);
?>
<div class="table-responsive">
    <table class="table table-striped table-hover bg-white align-middle">
        <thead class="table-success">
            <tr>
                <th>Farm</th><th>Owner</th><th>Crop</th><th>Category</th>
                <th class="text-end">Plantings</th><th class="text-end">Area planted (ha)</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No records found for this period.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= rpt_h($r['farm_name']) ?></td>
                <td><?= rpt_h($r['owner']) ?></td>
                <td><?= rpt_h(rpt_crop_label($r['crop_name'], $r['variety'])) ?></td>
                <td><?= rpt_h($r['category_name']) ?></td>
                <td class="text-end"><?= rpt_num($r['plantings'], 0) ?></td>
                <td class="text-end"><?= rpt_num($r['area']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if ($total > 0): ?>
    <p class="text-muted small">Showing <?= $offset + 1 ?>–<?= $offset + count($rows) ?> of <?= $total ?> rows</p>
<?php endif; ?>
<?php
rpt_pagination($total, $perPage, $page);
report_footer();
