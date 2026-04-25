<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\AuthController;

$controller = new AuthController();
$data = json_decode(file_get_contents("php://input"), true);

$controller->register($data);
