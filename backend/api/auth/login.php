<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\AuthController;

$controller = new AuthController();
$data = json_decode(file_get_contents("php://input"), true);

// Temporary debug logging (remove in production)
@mkdir(__DIR__ . '/../../logs', 0755, true);
file_put_contents(__DIR__ . '/../../logs/auth_debug.log', date('c') . " REQUEST: " . print_r($data, true) . PHP_EOL, FILE_APPEND);

$controller->login($data);
