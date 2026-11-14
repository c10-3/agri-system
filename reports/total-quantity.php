<?php
declare(strict_types=1);
require_once __DIR__ . '/_report_lib.php';

[$from, $to, $dateError] = rpt_dates();

$conds  = [];
$params = [];
rpt_date_conditions('h.harvest_date', $from, $to, $conds, $params);
$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$stmt = pdo()->prepare(
    "SELECT c.crop_name, c.variety,
            COUNT(h.harvest_id) AS harvests,
            SUM(h.quantity_kg) AS total_kg
     FROM harvest h
     JOIN planting p ON p.planting_id = h.planting_id
     JOIN crop c ON c.crop_id = p.crop_id
     $where
     GROUP BY c.crop_id, c.crop_name, c.variety
     ORDER BY total_kg DESC, c.crop_name"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$grandKg       = array_sum(array_map('floatval', array_column($rows, 'total_kg')));
$grandHarvests = array_sum(array_map('intval', array_column($rows, 'harvests')));

report_header('Total Harvested Quantity');
rpt_title('Total Harvested Quantity', rpt_period($from, $to), $dateError);
rpt_filter_form($from, $to, 'harvest date');
rpt_cards([
    ['Total harvested', rpt_num($grandKg) . ' kg'],
    ['Harvest records', rpt_num($grandHarvests, 0)],
    ['Crops harvested', rpt_num(count($rows), 0)],
]);
?>
<div class="table-responsive">
    <table class="table table-striped bg-white align-middle">
        <thead class="table-success">
            <tr>
                <th>Crop</th>
                <th class="text-end">Harvests</th>
                <th class="text-end">Total quantity (kg)</th>
                <th style="width:30%">Share of total</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">No harvest records found for this period.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r):
            $share = $grandKg > 0 ? ((float) $r['total_kg'] / $grandKg) * 100 : 0; ?>
            <tr>
                <td><?= rpt_h(rpt_crop_label($r['crop_name'], $r['variety'])) ?></td>
                <td class="text-end"><?= rpt_num($r['harvests'], 0) ?></td>
                <td class="text-end"><?= rpt_num($r['total_kg']) ?></td>
                <td>
                    <div class="progress" role="progressbar" aria-label="Share" aria-valuenow="<?= (int) round($share) ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-success" style="width: <?= round($share, 1) ?>%"></div>
                    </div>
                    <small class="text-muted"><?= round($share, 1) ?>%</small>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <?php if ($rows): ?>
        <tfoot class="table-light fw-bold">
            <tr>
                <td>Grand total</td>
                <td class="text-end"><?= rpt_num($grandHarvests, 0) ?></td>
                <td class="text-end"><?= rpt_num($grandKg) ?></td>
                <td></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
</div>
<?php report_footer();
