<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';

function crop_defaults(): array
{
    return ['crop_name' => '', 'variety' => '', 'category_id' => '', 'season_id' => '', 'growing_period_days' => ''];
}

function crop_lookups(): array
{
    return [
        'categories' => lookup_rows('crop_category', 'category_id', 'category_name'),
        'seasons'    => lookup_rows('season', 'season_id', 'season_name', 'season_id'),
    ];
}

function crop_validate(array $in, ?int $id): array
{
    $d = [];
    foreach (array_keys(crop_defaults()) as $k) {
        $d[$k] = trim((string) ($in[$k] ?? ''));
    }
    $e = [];

    if ($d['crop_name'] === '') {
        $e['crop_name'] = 'Crop name is required.';
    } elseif (text_len($d['crop_name']) > 100) {
        $e['crop_name'] = 'Crop name must be 100 characters or fewer.';
    } elseif (!preg_match("/^[\p{L}\p{N}][\p{L}\p{N} .'\-()]*$/u", $d['crop_name'])) {
        $e['crop_name'] = 'Crop name contains characters that are not allowed.';
    }

    if (text_len($d['variety']) > 100) {
        $e['variety'] = 'Variety must be 100 characters or fewer.';
    }

    $cat = filter_var($d['category_id'], FILTER_VALIDATE_INT);
    if ($d['category_id'] === '') {
        $e['category_id'] = 'Please select a category.';
    } elseif ($cat === false || !row_exists('crop_category', 'category_id', (int) $cat)) {
        $e['category_id'] = 'The selected category does not exist.';
    }

    $season = filter_var($d['season_id'], FILTER_VALIDATE_INT);
    if ($d['season_id'] === '') {
        $e['season_id'] = 'Please select a season.';
    } elseif ($season === false || !row_exists('season', 'season_id', (int) $season)) {
        $e['season_id'] = 'The selected season does not exist.';
    }

    $days = filter_var($d['growing_period_days'], FILTER_VALIDATE_INT);
    if ($d['growing_period_days'] === '') {
        $e['growing_period_days'] = 'Growing period is required.';
    } elseif ($days === false || $days < 1 || $days > 730) {
        $e['growing_period_days'] = 'Enter a whole number of days between 1 and 730.';
    }

    // Same crop name + variety cannot be saved twice (case-insensitive)
    if (!isset($e['crop_name']) && !isset($e['variety'])) {
        $sql = "SELECT 1 FROM crop WHERE LOWER(crop_name) = LOWER(:n) AND LOWER(COALESCE(variety, '')) = LOWER(:v)";
        $p   = [':n' => $d['crop_name'], ':v' => $d['variety']];
        if ($id !== null) {
            $sql .= ' AND crop_id <> :id';
            $p[':id'] = $id;
        }
        $stmt = pdo()->prepare($sql . ' LIMIT 1');
        $stmt->execute($p);
        if ($stmt->fetchColumn()) {
            $e['crop_name'] = 'This crop and variety already exists.';
        }
    }

    return [$e, $d];
}

function crop_find(int $id): ?array
{
    $stmt = pdo()->prepare('SELECT * FROM crop WHERE crop_id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}
