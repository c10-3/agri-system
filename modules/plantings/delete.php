<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';

handle_delete('plantings', 'planting', 'planting_id', 'planting record', ['harvest' => ['harvest', 'planting_id']]);
