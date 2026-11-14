<?php
declare(strict_types=1);
require_once __DIR__ . '/_report_lib.php';

[$from, $to, $dateError] = rpt_dates();

// Optional filter: quality grade
$gradeFilter = filter_var($_GET['grade_id'] ?? '', FILTER_VALIDATE_INT);
$gradeId     = ($gradeFilter === false) ? null : (int) $gradeFilter;

$conds  = [];
$params = [];
rpt_date_conditions('h.harvest_date', $from, $to, $conds, $params);
if ($gradeId !== null) {
    $conds[]          = 'h.grade_id = :grade';
    $params[':grade'] = $gradeId;
}
$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$fromSql = "FROM harvest h
            JOIN planting p ON p.planting_id = h.planting_id
            JOIN farm f ON f.farm_id = p.farm_id
            JOIN farmer fr ON fr.farmer_id = f.farmer_id
            JOIN crop c ON c.crop_id = p.crop_id
            LEFT JOIN quality_grade qg ON qg.grade_id = h.grade_id
            LEFT JOIN harvest_remark hr ON hr.remark_id = h.remark_id
            $where";

// Totals for the whole filtered report
$stmt = pdo()->prepare(
    "SELECT COUNT(*) AS records,
            COALESCE(SUM(h.quantity_kg), 0) AS total_kg,
            COALESCE(SUM(h.quantity_kg * COALESCE(h.selling_price_per_kg, 0)), 0) AS total_value
     $fromSql"
);
$stmt->execute($params);
$summary = $stmt->fetch();

$perPage = 15;
$total   = (int) $summary['records'];
$pages   = max(1, (int) ceil($total / $perPage));
$page    = min(rpt_page(), $pages);
$offset  = ($page - 1) * $perPage;

$stmt = pdo()->prepare(
    "SELECT h.harvest_date, f.farm_name, fr.last_name || ', ' || fr.first_name AS farmer,
            c.crop_name, c.variety, h.quantity_kg, h.selling_price_per_kg,
            h.quantity_kg * COALESCE(h.selling_price_per_kg, 0) AS est_value,
            qg.grade_name, hr.remark_description
     $fromSql
     ORDER BY h.harvest_date DESC, h.harvest_id DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$grades = pdo()->query('SELECT grade_id, grade_name FROM quality_grade ORDER BY grade_id')->fetchAll();
$extra  = rpt_select('grade_id', 'Quality grade', array_column($grades, 'grade_name', 'grade_id'),
                     $gradeId === null ? '' : (string) $gradeId, 'All grades');

report_header('Harvest Records');
rpt_title('Harvest Records', rpt_period($from, $to), $dateError);
rpt_filter_form($from, $to, 'harvest date', $extra);
rpt_cards([
    ['Harvest records',  rpt_num($summary['records'], 0)],
    ['Total quantity',   rpt_num($summary['total_kg']) . ' kg'],
    ['Estimated value',  rpt_money($summary['total_value'])],
]);
?>
<div class="table-responsive">
    <table class="table table-striped table-hover bg-white align-middle">
        <thead class="table-success">
            <tr>
                <th>Harvest date</th><th>Farm</th><th>Farmer</th><th>Crop</th>
                <th class="text-end">Quantity (kg)</th><th class="text-end">Price / kg</th>
                <th class="text-end">Estimated value</th><th>Grade</th><th>Remark</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">No records found for this period.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= rpt_h(rpt_date($r['harvest_date'])) ?></td>
                <td><?= rpt_h($r['farm_name']) ?></td>
                <td><?= rpt_h($r['farmer']) ?></td>
                <td><?= rpt_h(rpt_crop_label($r['crop_name'], $r['variety'])) ?></td>
                <td class="text-end"><?= rpt_num($r['quantity_kg']) ?></td>
                <td class="text-end"><?= rpt_money($r['selling_price_per_kg']) ?></td>
                <td class="text-end"><?= rpt_money($r['est_value']) ?></td>
                <td><?= rpt_h($r['grade_name'] ?? '—') ?></td>
                <td><?= rpt_h($r['remark_description'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if ($total > 0): ?>
    <p class="text-muted small">Showing <?= $offset + 1 ?>–<?= $offset + count($rows) ?> of <?= $total ?> records</p>
<?php endif; ?>
<?php
rpt_pagination($total, $perPage, $page);
report_footer();
