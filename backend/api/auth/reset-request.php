<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\ResetController;

$controller = new ResetController();
$data = json_decode(file_get_contents("php://input"), true);

$controller->requestReset($data);
