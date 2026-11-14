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
            SUM(h.quantity_kg) AS total_kg,
            SUM(h.quantity_kg * COALESCE(h.selling_price_per_kg, 0)) AS est_value
     FROM harvest h
     JOIN planting p ON p.planting_id = h.planting_id
     JOIN crop c ON c.crop_id = p.crop_id
     $where
     GROUP BY c.crop_id, c.crop_name, c.variety
     ORDER BY est_value DESC, c.crop_name"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$grandKg    = array_sum(array_map('floatval', array_column($rows, 'total_kg')));
$grandValue = array_sum(array_map('floatval', array_column($rows, 'est_value')));
$avgPrice   = $grandKg > 0 ? $grandValue / $grandKg : 0;

report_header('Estimated Harvest Value');
rpt_title('Estimated Harvest Value', rpt_period($from, $to), $dateError);
rpt_filter_form($from, $to, 'harvest date');
rpt_cards([
    ['Estimated value',  rpt_money($grandValue)],
    ['Total quantity',   rpt_num($grandKg) . ' kg'],
    ['Average price / kg', rpt_money($avgPrice)],
]);
?>
<p class="text-muted small">Estimated value = quantity (kg) × selling price per kg, added up for every harvest record.</p>
<div class="table-responsive">
    <table class="table table-striped bg-white align-middle">
        <thead class="table-success">
            <tr>
                <th>Crop</th>
                <th class="text-end">Quantity (kg)</th>
                <th class="text-end">Average price / kg</th>
                <th class="text-end">Estimated value</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">No harvest records found for this period.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r):
            $avg = (float) $r['total_kg'] > 0 ? (float) $r['est_value'] / (float) $r['total_kg'] : 0; ?>
            <tr>
                <td><?= rpt_h(rpt_crop_label($r['crop_name'], $r['variety'])) ?></td>
                <td class="text-end"><?= rpt_num($r['total_kg']) ?></td>
                <td class="text-end"><?= rpt_money($avg) ?></td>
                <td class="text-end"><?= rpt_money($r['est_value']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <?php if ($rows): ?>
        <tfoot class="table-light fw-bold">
            <tr>
                <td>Grand total</td>
                <td class="text-end"><?= rpt_num($grandKg) ?></td>
                <td class="text-end"><?= rpt_money($avgPrice) ?></td>
                <td class="text-end"><?= rpt_money($grandValue) ?></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
</div>
<?php report_footer();
