<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';

function farmer_defaults(): array
{
    return [
        'first_name'      => '',
        'last_name'       => '',
        'contact_number'  => '',
        'address'         => '',
        'date_registered' => date('Y-m-d'),
    ];
}

/** Returns [errors, cleaned data]. $id is the farmer being edited (null when adding). */
function farmer_validate(array $in, ?int $id): array
{
    $d = [
        'first_name'      => trim((string) ($in['first_name'] ?? '')),
        'last_name'       => trim((string) ($in['last_name'] ?? '')),
        'contact_number'  => preg_replace('/[\s\-]+/', '', (string) ($in['contact_number'] ?? '')),
        'address'         => trim((string) ($in['address'] ?? '')),
        'date_registered' => trim((string) ($in['date_registered'] ?? '')),
    ];
    $e = [];

    foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $f => $label) {
        if ($d[$f] === '') {
            $e[$f] = "$label is required.";
        } elseif (text_len($d[$f]) > 50) {
            $e[$f] = "$label must be 50 characters or fewer.";
        } elseif (!preg_match("/^\p{L}[\p{L} .'\-]*$/u", $d[$f])) {
            $e[$f] = "$label may contain letters, spaces, hyphens, apostrophes and periods only.";
        }
    }

    if ($d['contact_number'] === '') {
        $e['contact_number'] = 'Contact number is required.';
    } elseif (!preg_match('/^\+?\d{7,15}$/', $d['contact_number'])) {
        $e['contact_number'] = 'Enter a valid contact number (7 to 15 digits, for example 09171234567).';
    } elseif (value_taken('farmer', 'contact_number', $d['contact_number'], 'farmer_id', $id)) {
        $e['contact_number'] = 'This contact number is already registered to another farmer.';
    }

    if ($d['address'] === '') {
        $e['address'] = 'Address is required.';
    } elseif (text_len($d['address']) > 255) {
        $e['address'] = 'Address must be 255 characters or fewer.';
    }

    if ($d['date_registered'] === '') {
        $e['date_registered'] = 'Date registered is required.';
    } elseif (!valid_date_str($d['date_registered'])) {
        $e['date_registered'] = 'Enter a valid date.';
    } elseif ($d['date_registered'] > date('Y-m-d')) {
        $e['date_registered'] = 'Date registered cannot be in the future.';
    }

    return [$e, $d];
}

function farmer_find(int $id): ?array
{
    $stmt = pdo()->prepare('SELECT * FROM farmer WHERE farmer_id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}
