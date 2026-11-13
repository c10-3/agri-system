<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';

handle_delete('crops', 'crop', 'crop_id', 'crop', ['planting' => ['planting', 'crop_id']]);
