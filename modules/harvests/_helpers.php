<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';

function harvest_defaults(): array
{
    return [
        'planting_id' => '', 'grade_id' => '', 'remark_id' => '', 'harvest_date' => date('Y-m-d'),
        'quantity_kg' => '', 'selling_price_per_kg' => '',
    ];
}

function harvest_lookups(): array
{
    return [
        'plantings' => pdo()->query(
            "SELECT p.planting_id,
                    fm.farm_name || ' — ' || c.crop_name
                      || CASE WHEN c.variety IS NULL OR c.variety = '' THEN '' ELSE ' (' || c.variety || ')' END
                      || ' — planted ' || to_char(p.planting_date, 'YYYY-MM-DD') AS label
               FROM planting p
               JOIN farm fm ON fm.farm_id = p.farm_id
               JOIN crop c ON c.crop_id = p.crop_id
              ORDER BY p.planting_date DESC, fm.farm_name"
        )->fetchAll(),
        'grades'  => lookup_rows('quality_grade', 'grade_id', 'grade_name', 'grade_id'),
        'remarks' => lookup_rows('harvest_remark', 'remark_id', 'remark_description', 'remark_id'),
        'farms'   => lookup_rows('farm', 'farm_id', 'farm_name'),
        'crops'   => pdo()->query(
            "SELECT crop_id, crop_name || CASE WHEN variety IS NULL OR variety = '' THEN '' ELSE ' (' || variety || ')' END AS label
               FROM crop ORDER BY crop_name, variety"
        )->fetchAll(),
    ];
}

function harvest_validate(array $in, ?int $id): array
{
    $d = [];
    foreach (array_keys(harvest_defaults()) as $k) {
        $d[$k] = trim((string) ($in[$k] ?? ''));
    }
    $e = [];

    $plantingId   = filter_var($d['planting_id'], FILTER_VALIDATE_INT);
    $plantingDate = null;
    if ($d['planting_id'] === '') {
        $e['planting_id'] = 'Please select the planting this harvest came from.';
    } elseif ($plantingId === false) {
        $e['planting_id'] = 'The selected planting does not exist.';
    } else {
        $stmt = pdo()->prepare('SELECT planting_date FROM planting WHERE planting_id = :id');
        $stmt->execute([':id' => (int) $plantingId]);
        $plantingDate = $stmt->fetchColumn();
        if ($plantingDate === false) {
            $e['planting_id'] = 'The selected planting does not exist.';
        }
    }

    $grade = filter_var($d['grade_id'], FILTER_VALIDATE_INT);
    if ($d['grade_id'] === '') {
        $e['grade_id'] = 'Please select a quality grade.';
    } elseif ($grade === false || !row_exists('quality_grade', 'grade_id', (int) $grade)) {
        $e['grade_id'] = 'The selected grade does not exist.';
    }

    $remark = filter_var($d['remark_id'], FILTER_VALIDATE_INT);
    if ($d['remark_id'] === '') {
        $e['remark_id'] = 'Please select a remark.';
    } elseif ($remark === false || !row_exists('harvest_remark', 'remark_id', (int) $remark)) {
        $e['remark_id'] = 'The selected remark does not exist.';
    }

    if ($d['harvest_date'] === '') {
        $e['harvest_date'] = 'Harvest date is required.';
    } elseif (!valid_date_str($d['harvest_date'])) {
        $e['harvest_date'] = 'Enter a valid date.';
    } elseif ($d['harvest_date'] > date('Y-m-d')) {
        $e['harvest_date'] = 'Harvest date cannot be in the future.';
    } elseif ($plantingDate && $d['harvest_date'] < (string) $plantingDate) {
        $e['harvest_date'] = 'Harvest date cannot be before the planting date (' . date('M j, Y', strtotime((string) $plantingDate)) . ').';
    }

    if ($d['quantity_kg'] === '') {
        $e['quantity_kg'] = 'Quantity is required.';
    } else {
        $qty = parse_decimal($d['quantity_kg']);
        if ($qty === null || $qty <= 0) {
            $e['quantity_kg'] = 'Enter a quantity greater than 0 (up to 2 decimal places).';
        }
    }

    if ($d['selling_price_per_kg'] === '') {
        $e['selling_price_per_kg'] = 'Selling price per kg is required.';
    } elseif (parse_decimal($d['selling_price_per_kg']) === null) {
        $e['selling_price_per_kg'] = 'Enter a price of 0 or more (up to 2 decimal places).';
    }

    return [$e, $d];
}

function harvest_find(int $id): ?array
{
    $stmt = pdo()->prepare('SELECT * FROM harvest WHERE harvest_id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

function harvest_params(array $d): array
{
    return [
        ':pl' => (int) $d['planting_id'], ':gr' => (int) $d['grade_id'], ':re' => (int) $d['remark_id'],
        ':hd' => $d['harvest_date'], ':qt' => $d['quantity_kg'], ':pr' => $d['selling_price_per_kg'],
    ];
}
