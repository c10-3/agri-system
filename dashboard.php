<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'Dashboard';

/* ---- Summary cards (counts read live from the database) ---- */
$cards = [
    'Farmers'   => ['farmer',   'modules/farmers/index.php',   'farmers',   '🧑‍🌾'],
    'Farms'     => ['farm',     'modules/farms/index.php',     'farms',     '🏡'],
    'Crops'     => ['crop',     'modules/crops/index.php',     'crops',     '🌽'],
    'Plantings' => ['planting', 'modules/plantings/index.php', 'plantings', '🌱'],
    'Harvests'  => ['harvest',  'modules/harvests/index.php',  'harvests',  '🧺'],
];

/* ---- Harvest totals ---- */
$totals = db_rows(
    "SELECT COALESCE(SUM(quantity_kg), 0) AS total_kg,
            COALESCE(SUM(quantity_kg * selling_price_per_kg), 0) AS total_value
     FROM harvest"
)[0] ?? ['total_kg' => 0, 'total_value' => 0];

/* ---- Plantings by status ---- */
$statusRows = db_rows(
    "SELECT ps.status_name AS status, COUNT(p.planting_id) AS total
     FROM planting_status ps
     LEFT JOIN planting p ON p.status_id = ps.status_id
     GROUP BY ps.status_id, ps.status_name
     HAVING COUNT(p.planting_id) > 0
     ORDER BY total DESC, ps.status_name"
);

/* ---- Harvest quantity by crop (top 6) ---- */
$cropRows = db_rows(
    "SELECT c.crop_name, COALESCE(SUM(h.quantity_kg), 0) AS total_kg
     FROM harvest h
     JOIN planting p ON p.planting_id = h.planting_id
     JOIN crop c     ON c.crop_id     = p.crop_id
     GROUP BY c.crop_name
     ORDER BY total_kg DESC
     LIMIT 6"
);

/* ---- Recent plantings (joined with farm, farmer and crop) ---- */
$recentPlantings = db_rows(
    "SELECT p.planting_id, p.planting_date, p.expected_harvest_date, ps.status_name AS status,
            f.farm_name, c.crop_name, c.variety
     FROM planting p
     JOIN farm f ON f.farm_id = p.farm_id
     JOIN crop c ON c.crop_id = p.crop_id
     JOIN planting_status ps ON ps.status_id = p.status_id
     ORDER BY p.planting_date DESC, p.planting_id DESC
     LIMIT 5"
);

/* ---- Recent harvests (joined with planting, farm and crop) ---- */
$recentHarvests = db_rows(
    "SELECT h.harvest_id, h.harvest_date, h.quantity_kg, qg.grade_name AS quality_grade,
            f.farm_name, c.crop_name
     FROM harvest h
     JOIN planting p ON p.planting_id = h.planting_id
     JOIN farm f     ON f.farm_id     = p.farm_id
     JOIN crop c     ON c.crop_id     = p.crop_id
     LEFT JOIN quality_grade qg ON qg.grade_id = h.grade_id
     ORDER BY h.harvest_date DESC, h.harvest_id DESC
     LIMIT 5"
);

function status_badge(string $status): string
{
    return match (strtolower($status)) {
        'planted'           => 'secondary',
        'growing'           => 'primary',
        'ready for harvest' => 'warning text-dark',
        'harvested'         => 'success',
        'failed'            => 'danger',
        default             => 'dark',
    };
}

function status_color(string $status): string
{
    return match (strtolower($status)) {
        'planted'           => '#8a5a2b',
        'growing'           => '#5f9a2c',
        'ready for harvest' => '#e0a400',
        'harvested'         => '#2f5d1a',
        'failed'            => '#b3382c',
        default             => '#6b5a42',
    };
}

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Dashboard</h1>
    <span class="text-muted small">Live data from the database</span>
</div>

<!-- Summary cards: each one links to its module and has its own color -->
<div class="row g-3 mb-4">
<?php foreach ($cards as $label => [$table, $link, $key, $icon]): $n = safe_count($table); ?>
    <div class="col-6 col-md-4 col-lg">
        <a href="<?= BASE_URL . '/' . $link ?>" class="text-decoration-none">
            <div class="card stat-card stat-<?= $key ?> shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon d-flex align-items-center justify-content-center" aria-hidden="true"><?= $icon ?></div>
                    <div>
                        <div class="stat-label"><?= e($label) ?></div>
                        <div class="stat-number"><?= $n === null ? 'n/a' : number_format($n) ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
<?php endforeach; ?>
</div>

<!-- Harvest totals -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <div class="card kpi kpi-kg shadow-sm h-100">
            <div class="card-body">
                <div class="kpi-label">Total harvested quantity</div>
                <div class="kpi-number"><?= number_format((float) $totals['total_kg'], 2) ?> <small class="fs-5">kg</small></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card kpi kpi-value shadow-sm h-100">
            <div class="card-body">
                <div class="kpi-label">Estimated harvest value</div>
                <div class="kpi-number">₱<?= number_format((float) $totals['total_value'], 2) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Plantings by status -->
    <div class="col-12 col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Plantings by status</div>
            <div class="card-body">
                <?php if (!$statusRows): ?>
                    <p class="text-muted mb-0">No plantings yet. Add one from the Plantings page.</p>
                <?php else: ?>
                    <div style="max-height:220px">
                        <canvas id="statusChart" height="220" aria-label="Plantings by status chart" role="img"></canvas>
                    </div>
                    <table class="table table-sm mt-3 mb-0">
                        <thead><tr><th>Status</th><th class="text-end">Plantings</th></tr></thead>
                        <tbody>
                        <?php foreach ($statusRows as $s): ?>
                            <tr>
                                <td><span class="badge bg-<?= status_badge((string) $s['status']) ?>"><?= e((string) $s['status']) ?></span></td>
                                <td class="text-end"><?= number_format((int) $s['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Harvest by crop -->
    <div class="col-12 col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Harvest by crop (kg)</div>
            <div class="card-body">
                <?php if (!$cropRows): ?>
                    <p class="text-muted mb-0">No harvests yet. Record one from the Harvests page.</p>
                <?php else: ?>
                    <div style="position:relative;height:300px">
                        <canvas id="cropChart" aria-label="Harvest quantity by crop chart" role="img"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent plantings -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Recent plantings</span>
        <a href="<?= BASE_URL ?>/modules/plantings/index.php" class="small">View all</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Farm</th><th>Crop</th><th>Planted</th><th>Expected Harvest</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php if (!$recentPlantings): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No planting records yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($recentPlantings as $r): ?>
                <tr>
                    <td><?= e($r['farm_name']) ?></td>
                    <td><?= e($r['crop_name']) ?><?= !empty($r['variety']) ? ' <span class="text-muted small">(' . e($r['variety']) . ')</span>' : '' ?></td>
                    <td><?= e(fmt_date($r['planting_date'])) ?></td>
                    <td><?= e(fmt_date($r['expected_harvest_date'])) ?></td>
                    <td><span class="badge bg-<?= status_badge((string) $r['status']) ?>"><?= e($r['status'] ?: 'Unspecified') ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent harvests -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Recent harvests</span>
        <a href="<?= BASE_URL ?>/modules/harvests/index.php" class="small">View all</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Harvest Date</th><th>Farm</th><th>Crop</th><th class="text-end">Quantity (kg)</th><th>Quality Grade</th></tr>
            </thead>
            <tbody>
            <?php if (!$recentHarvests): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No harvest records yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($recentHarvests as $h): ?>
                <tr>
                    <td><?= e(fmt_date($h['harvest_date'])) ?></td>
                    <td><?= e($h['farm_name']) ?></td>
                    <td><?= e($h['crop_name']) ?></td>
                    <td class="text-end"><?= number_format((float) $h['quantity_kg'], 2) ?></td>
                    <td><?= e($h['quality_grade'] ?: '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($statusRows || $cropRows): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = "'Figtree', system-ui, sans-serif";
<?php if ($statusRows): ?>
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($statusRows, 'status'), JSON_HEX_TAG | JSON_HEX_AMP) ?>,
            datasets: [{
                data: <?= json_encode(array_map('intval', array_column($statusRows, 'total'))) ?>,
                backgroundColor: <?= json_encode(array_map(fn($r) => status_color((string) $r['status']), $statusRows)) ?>
            }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });
<?php endif; ?>
<?php if ($cropRows): ?>
    new Chart(document.getElementById('cropChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($cropRows, 'crop_name'), JSON_HEX_TAG | JSON_HEX_AMP) ?>,
            datasets: [{
                label: 'Harvested (kg)',
                data: <?= json_encode(array_map('floatval', array_column($cropRows, 'total_kg'))) ?>,
                backgroundColor: ['#3f7d20', '#c2610f', '#a8780a', '#2e7d57', '#8a5a2b', '#7fae3c'],
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, grid: { color: '#e6dab8' } }, y: { grid: { display: false } } }
        }
    });
<?php endif; ?>
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?> 