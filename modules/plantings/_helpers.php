<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';

function planting_defaults(): array
{
    return [
        'farm_id' => '', 'crop_id' => '', 'status_id' => '', 'planting_date' => date('Y-m-d'),
        'area_planted' => '', 'seed_quantity' => '', 'expected_harvest_date' => '',
    ];
}

function planting_lookups(): array
{
    return [
        'farms' => pdo()->query(
            "SELECT f.farm_id, f.farm_name || ' — ' || fr.last_name || ', ' || fr.first_name AS label
               FROM farm f JOIN farmer fr ON fr.farmer_id = f.farmer_id ORDER BY f.farm_name, fr.last_name"
        )->fetchAll(),
        'crops' => pdo()->query(
            "SELECT crop_id, crop_name || CASE WHEN variety IS NULL OR variety = '' THEN '' ELSE ' (' || variety || ')' END AS label
               FROM crop ORDER BY crop_name, variety"
        )->fetchAll(),
        'statuses' => lookup_rows('planting_status', 'status_id', 'status_name', 'status_id'),
    ];
}

function planting_validate(array $in, ?int $id): array
{
    $d = [];
    foreach (array_keys(planting_defaults()) as $k) {
        $d[$k] = trim((string) ($in[$k] ?? ''));
    }
    $e = [];

    $farmId = filter_var($d['farm_id'], FILTER_VALIDATE_INT);
    if ($d['farm_id'] === '') {
        $e['farm_id'] = 'Please select a farm.';
    } elseif ($farmId === false || !row_exists('farm', 'farm_id', (int) $farmId)) {
        $e['farm_id'] = 'The selected farm does not exist.';
    }

    $cropId = filter_var($d['crop_id'], FILTER_VALIDATE_INT);
    if ($d['crop_id'] === '') {
        $e['crop_id'] = 'Please select a crop.';
    } elseif ($cropId === false || !row_exists('crop', 'crop_id', (int) $cropId)) {
        $e['crop_id'] = 'The selected crop does not exist.';
    }

    $statusId = filter_var($d['status_id'], FILTER_VALIDATE_INT);
    if ($d['status_id'] === '') {
        $e['status_id'] = 'Please select a status.';
    } elseif ($statusId === false || !row_exists('planting_status', 'status_id', (int) $statusId)) {
        $e['status_id'] = 'The selected status does not exist.';
    }

    if ($d['planting_date'] === '') {
        $e['planting_date'] = 'Planting date is required.';
    } elseif (!valid_date_str($d['planting_date'])) {
        $e['planting_date'] = 'Enter a valid date.';
    }

    if ($d['area_planted'] === '') {
        $e['area_planted'] = 'Area planted is required.';
    } else {
        $area = parse_decimal($d['area_planted']);
        if ($area === null || $area <= 0) {
            $e['area_planted'] = 'Enter a number greater than 0 (up to 2 decimal places).';
        } elseif (!isset($e['farm_id'])) {
            $stmt = pdo()->prepare('SELECT area_hectares FROM farm WHERE farm_id = :id');
            $stmt->execute([':id' => (int) $farmId]);
            $farmArea = $stmt->fetchColumn();
            if ($farmArea !== false && $farmArea !== null && $area > (float) $farmArea) {
                $e['area_planted'] = 'Area planted cannot be larger than the farm (' . number_format((float) $farmArea, 2) . ' ha).';
            }
        }
    }

    if ($d['seed_quantity'] === '') {
        $e['seed_quantity'] = 'Seed quantity is required.';
    } else {
        $seed = parse_decimal($d['seed_quantity']);
        if ($seed === null) {
            $e['seed_quantity'] = 'Enter a number of 0 or more (up to 2 decimal places).';
        }
    }

    if ($d['expected_harvest_date'] !== '') {
        if (!valid_date_str($d['expected_harvest_date'])) {
            $e['expected_harvest_date'] = 'Enter a valid date.';
        } elseif (!isset($e['planting_date']) && $d['expected_harvest_date'] < $d['planting_date']) {
            $e['expected_harvest_date'] = 'Expected harvest date cannot be before the planting date.';
        }
    }

    return [$e, $d];
}

function planting_find(int $id): ?array
{
    $stmt = pdo()->prepare('SELECT * FROM planting WHERE planting_id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

function planting_params(array $d): array
{
    return [
        ':fa' => (int) $d['farm_id'], ':cr' => (int) $d['crop_id'], ':st' => (int) $d['status_id'],
        ':pd' => $d['planting_date'], ':ap' => $d['area_planted'], ':sq' => $d['seed_quantity'],
        ':eh' => $d['expected_harvest_date'] !== '' ? $d['expected_harvest_date'] : null,
    ];
}
