<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';

handle_delete('farms', 'farm', 'farm_id', 'farm', ['planting' => ['planting', 'farm_id']]);
