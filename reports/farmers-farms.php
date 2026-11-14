<?php
declare(strict_types=1);
require_once __DIR__ . '/_report_lib.php';

[$from, $to, $dateError] = rpt_dates();

$conds  = [];
$params = [];
rpt_date_conditions('fr.date_registered', $from, $to, $conds, $params);
$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$fromSql = "FROM farmer fr
            LEFT JOIN farm f ON f.farmer_id = fr.farmer_id
            LEFT JOIN soil_type st ON st.soil_type_id = f.soil_type_id
            LEFT JOIN irrigation_type it ON it.irrigation_id = f.irrigation_id
            $where";

// Totals for the whole filtered report (not just the current page)
$stmt = pdo()->prepare(
    "SELECT COUNT(*) AS row_count,
            COUNT(DISTINCT fr.farmer_id) AS farmers,
            COUNT(f.farm_id) AS farms,
            COALESCE(SUM(f.area_hectares), 0) AS area
     $fromSql"
);
$stmt->execute($params);
$summary = $stmt->fetch();

$perPage = 15;
$total   = (int) $summary['row_count'];
$pages   = max(1, (int) ceil($total / $perPage));
$page    = min(rpt_page(), $pages);
$offset  = ($page - 1) * $perPage;

$stmt = pdo()->prepare(
    "SELECT fr.last_name || ', ' || fr.first_name AS farmer, fr.contact_number, fr.address, fr.date_registered,
            f.farm_name, f.location, f.area_hectares, st.soil_type_name, it.irrigation_name
     $fromSql
     ORDER BY fr.last_name, fr.first_name, f.farm_name
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

report_header('Farmers and Their Farms');
rpt_title('Farmers and Their Farms', rpt_period($from, $to), $dateError);
rpt_filter_form($from, $to, 'date registered');
rpt_cards([
    ['Farmers',     rpt_num($summary['farmers'], 0)],
    ['Farms',       rpt_num($summary['farms'], 0)],
    ['Total area',  rpt_num($summary['area']) . ' ha'],
]);
?>
<div class="table-responsive">
    <table class="table table-striped table-hover bg-white align-middle">
        <thead class="table-success">
            <tr>
                <th>Farmer</th><th>Contact</th><th>Address</th><th>Registered</th>
                <th>Farm</th><th>Location</th><th class="text-end">Area (ha)</th><th>Soil</th><th>Irrigation</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">No records found for this period.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= rpt_h($r['farmer']) ?></td>
                <td><?= rpt_h($r['contact_number']) ?></td>
                <td><?= rpt_h($r['address']) ?></td>
                <td><?= rpt_h(rpt_date($r['date_registered'])) ?></td>
                <?php if ($r['farm_name'] === null): ?>
                    <td colspan="5" class="text-muted fst-italic">No farm registered</td>
                <?php else: ?>
                    <td><?= rpt_h($r['farm_name']) ?></td>
                    <td><?= rpt_h($r['location']) ?></td>
                    <td class="text-end"><?= rpt_num($r['area_hectares']) ?></td>
                    <td><?= rpt_h($r['soil_type_name']) ?></td>
                    <td><?= rpt_h($r['irrigation_name']) ?></td>
                <?php endif; ?>
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
