<?php
declare(strict_types=1);
require_once __DIR__ . '/_report_lib.php';

[$from, $to, $dateError] = rpt_dates();

$conds  = [];
$params = [];
rpt_date_conditions('p.planting_date', $from, $to, $conds, $params);
// The date filter goes in the JOIN so statuses with zero plantings still appear.
$on = $conds ? ' AND ' . implode(' AND ', $conds) : '';

$stmt = pdo()->prepare(
    "SELECT ps.status_name,
            COUNT(p.planting_id) AS plantings,
            COALESCE(SUM(p.area_planted), 0) AS area
     FROM planting_status ps
     LEFT JOIN planting p ON p.status_id = ps.status_id$on
     GROUP BY ps.status_id, ps.status_name
     ORDER BY ps.status_id"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$totalPlantings = array_sum(array_map('intval', array_column($rows, 'plantings')));
$totalArea      = array_sum(array_map('floatval', array_column($rows, 'area')));

report_header('Plantings by Status');
rpt_title('Plantings by Status', rpt_period($from, $to), $dateError);
rpt_filter_form($from, $to, 'planting date');
rpt_cards([
    ['Total plantings', rpt_num($totalPlantings, 0)],
    ['Total area planted', rpt_num($totalArea) . ' ha'],
]);
?>
<div class="table-responsive">
    <table class="table table-striped bg-white align-middle">
        <thead class="table-success">
            <tr>
                <th>Status</th>
                <th class="text-end">Plantings</th>
                <th class="text-end">Area planted (ha)</th>
                <th style="width:30%">Share of plantings</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
            $share = $totalPlantings > 0 ? ((int) $r['plantings'] / $totalPlantings) * 100 : 0; ?>
            <tr>
                <td><?= rpt_h($r['status_name']) ?></td>
                <td class="text-end"><?= rpt_num($r['plantings'], 0) ?></td>
                <td class="text-end"><?= rpt_num($r['area']) ?></td>
                <td>
                    <div class="progress" role="progressbar" aria-label="Share" aria-valuenow="<?= (int) round($share) ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-success" style="width: <?= round($share, 1) ?>%"></div>
                    </div>
                    <small class="text-muted"><?= round($share, 1) ?>%</small>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td>Total</td>
                <td class="text-end"><?= rpt_num($totalPlantings, 0) ?></td>
                <td class="text-end"><?= rpt_num($totalArea) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
<?php report_footer();
