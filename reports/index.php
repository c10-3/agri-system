<?php
declare(strict_types=1);
require_once __DIR__ . '/_report_lib.php';

$reports = [
    ['Farmers and Their Farms',      'Every farmer with their farms, location, area, soil type and irrigation.', 'farmers-farms.php'],
    ['Crops Planted per Farm',       'Which crops each farm planted, how many plantings and how much area.',     'crops-per-farm.php'],
    ['Plantings by Status',          'How many plantings are Planted, Growing, Harvested and so on.',            'plantings-by-status.php'],
    ['Harvest Records',              'Every harvest with farm, crop, quantity, price, grade and estimated value.', 'harvests.php'],
    ['Total Harvested Quantity',     'Total kilograms harvested per crop.',                                      'total-quantity.php'],
    ['Estimated Harvest Value',      'Quantity × selling price, per crop and in total.',                         'harvest-value.php'],
];

report_header('Reports');
?>
<h1 class="h3 mb-1">Reports</h1>
<p class="text-muted mb-4">Choose a report. Every report can be filtered by date range and printed.</p>

<div class="row g-3">
    <?php foreach ($reports as [$title, $desc, $file]): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <h2 class="h5 text-success"><?= rpt_h($title) ?></h2>
                    <p class="text-muted flex-grow-1"><?= rpt_h($desc) ?></p>
                    <a class="btn btn-success" href="<?= rpt_h($file) ?>">Open report</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php report_footer(); ?>
