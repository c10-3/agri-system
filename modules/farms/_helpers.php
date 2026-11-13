<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';

function farm_defaults(): array
{
    return ['farmer_id' => '', 'farm_name' => '', 'location' => '', 'area_hectares' => '', 'soil_type_id' => '', 'irrigation_id' => ''];
}

function farm_lookups(): array
{
    return [
        'farmers'     => pdo()->query("SELECT farmer_id, last_name || ', ' || first_name AS label FROM farmer ORDER BY last_name, first_name")->fetchAll(),
        'soils'       => lookup_rows('soil_type', 'soil_type_id', 'soil_type_name'),
        'irrigations' => lookup_rows('irrigation_type', 'irrigation_id', 'irrigation_name'),
    ];
}

function farm_validate(array $in, ?int $id): array
{
    $d = [];
    foreach (array_keys(farm_defaults()) as $k) {
        $d[$k] = trim((string) ($in[$k] ?? ''));
    }
    $e = [];

    $farmerId = filter_var($d['farmer_id'], FILTER_VALIDATE_INT);
    if ($d['farmer_id'] === '') {
        $e['farmer_id'] = 'Please select the farmer who owns this farm.';
    } elseif ($farmerId === false || !row_exists('farmer', 'farmer_id', (int) $farmerId)) {
        $e['farmer_id'] = 'The selected farmer does not exist.';
    }

    if ($d['farm_name'] === '') {
        $e['farm_name'] = 'Farm name is required.';
    } elseif (text_len($d['farm_name']) > 100) {
        $e['farm_name'] = 'Farm name must be 100 characters or fewer.';
    }

    if ($d['location'] === '') {
        $e['location'] = 'Location is required.';
    } elseif (text_len($d['location']) > 255) {
        $e['location'] = 'Location must be 255 characters or fewer.';
    }

    if ($d['area_hectares'] === '') {
        $e['area_hectares'] = 'Area in hectares is required.';
    } else {
        $area = parse_decimal($d['area_hectares']);
        if ($area === null || $area <= 0) {
            $e['area_hectares'] = 'Enter a number greater than 0 (up to 2 decimal places).';
        }
    }

    $soilId = filter_var($d['soil_type_id'], FILTER_VALIDATE_INT);
    if ($d['soil_type_id'] === '') {
        $e['soil_type_id'] = 'Please select a soil type.';
    } elseif ($soilId === false || !row_exists('soil_type', 'soil_type_id', (int) $soilId)) {
        $e['soil_type_id'] = 'The selected soil type does not exist.';
    }

    $irrId = filter_var($d['irrigation_id'], FILTER_VALIDATE_INT);
    if ($d['irrigation_id'] === '') {
        $e['irrigation_id'] = 'Please select an irrigation type.';
    } elseif ($irrId === false || !row_exists('irrigation_type', 'irrigation_id', (int) $irrId)) {
        $e['irrigation_id'] = 'The selected irrigation type does not exist.';
    }

    // The same farmer cannot have two farms with the same name
    if (!isset($e['farmer_id']) && !isset($e['farm_name'])) {
        $sql = 'SELECT 1 FROM farm WHERE farmer_id = :f AND LOWER(farm_name) = LOWER(:n)';
        $p   = [':f' => (int) $farmerId, ':n' => $d['farm_name']];
        if ($id !== null) {
            $sql .= ' AND farm_id <> :id';
            $p[':id'] = $id;
        }
        $stmt = pdo()->prepare($sql . ' LIMIT 1');
        $stmt->execute($p);
        if ($stmt->fetchColumn()) {
            $e['farm_name'] = 'This farmer already has a farm with that name.';
        }
    }

    return [$e, $d];
}

function farm_find(int $id): ?array
{
    $stmt = pdo()->prepare('SELECT * FROM farm WHERE farm_id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}
