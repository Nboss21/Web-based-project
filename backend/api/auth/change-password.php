<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

$user = AuthMiddleware::authenticate();
$controller = new AuthController();

$data = json_decode(file_get_contents("php://input"), true);
$controller->changePassword($user['user_id'], $data);
