<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'Dashboard';

/* ---- Summary cards (counts read live from the database) ---- */
$cards = [
    'Farmers'   => ['farmer',   'modules/farmers/index.php'],
    'Farms'     => ['farm',     'modules/farms/index.php'],
    'Crops'     => ['crop',     'modules/crops/index.php'],
    'Plantings' => ['planting', 'modules/plantings/index.php'],
    'Harvests'  => ['harvest',  'modules/harvests/index.php'],
];

/* ---- Harvest totals ---- */
$totals = db_rows(
    "SELECT COALESCE(SUM(h.quantity_kg), 0) AS total_kg,
            COALESCE(SUM(h.quantity_kg * h.selling_price_per_kg), 0) AS total_value
     FROM harvest h
     JOIN planting p ON p.planting_id = h.planting_id
     WHERE p.status IS DISTINCT FROM 'Failed'"
)[0] ?? ['total_kg' => 0, 'total_value' => 0];

/* ---- Plantings by status ---- */
$statusRows = db_rows(
    "SELECT COALESCE(NULLIF(TRIM(status), ''), 'Unspecified') AS status, COUNT(*) AS total
     FROM planting
     GROUP BY 1
     ORDER BY total DESC, status"
);

/* ---- Recent plantings (joined with farm, farmer and crop) ---- */
$recentPlantings = db_rows(
    "SELECT p.planting_id, p.planting_date, p.expected_harvest_date, p.status,
            f.farm_name, c.crop_name, c.variety
     FROM planting p
     JOIN farm f ON f.farm_id = p.farm_id
     JOIN crop c ON c.crop_id = p.crop_id
     ORDER BY p.planting_date DESC, p.planting_id DESC
     LIMIT 5"
);

/* ---- Recent harvests (joined with planting, farm and crop) ---- */
$recentHarvests = db_rows(
    "SELECT h.harvest_id, h.harvest_date, h.quantity_kg, h.quality_grade,
            f.farm_name, c.crop_name
     FROM harvest h
     JOIN planting p ON p.planting_id = h.planting_id
     JOIN farm f     ON f.farm_id     = p.farm_id
     JOIN crop c     ON c.crop_id     = p.crop_id
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
        'planted'           => '#6c757d',
        'growing'           => '#0d6efd',
        'ready for harvest' => '#ffc107',
        'harvested'         => '#198754',
        'failed'            => '#dc3545',
        default             => '#212529',
    };
}

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Dashboard</h3>
    <span class="text-muted small">Live data from the database</span>
</div>

<!-- Summary cards: each one links to its module -->
<div class="row g-3 mb-4">
<?php foreach ($cards as $label => [$table, $link]): $n = safe_count($table); ?>
    <div class="col-6 col-md-4 col-lg">
        <a href="<?= BASE_URL . '/' . $link ?>" class="text-decoration-none">
            <div class="card stat-card shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="text-muted"><?= e($label) ?></div>
                    <div class="display-6"><?= $n === null ? 'n/a' : number_format($n) ?></div>
                </div>
            </div>
        </a>
    </div>
<?php endforeach; ?>
</div>

<!-- Harvest totals -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted">Total Harvested Quantity</div>
                <div class="display-6"><?= number_format((float) $totals['total_kg'], 2) ?> <small class="fs-5">kg</small></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted">Estimated Harvest Value</div>
                <div class="display-6">₱<?= number_format((float) $totals['total_value'], 2) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Plantings by status -->
    <div class="col-12 col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Plantings by Status</div>
            <div class="card-body">
                <?php if (!$statusRows): ?>
                    <p class="text-muted mb-0">No planting records yet.</p>
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

    <!-- Recent plantings -->
    <div class="col-12 col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Recent Plantings</span>
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
    </div>
</div>

<!-- Recent harvests -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Recent Harvests</span>
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

<?php if ($statusRows): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
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
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>