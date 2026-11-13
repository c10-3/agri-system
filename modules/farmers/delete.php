<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';

handle_delete('farmers', 'farmer', 'farmer_id', 'farmer', ['farm' => ['farm', 'farmer_id']]);
