<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;

$user = AuthMiddleware::authenticate('Admin');
$controller = new UserController();

$data = json_decode(file_get_contents("php://input"), true);
$controller->createUser($data, $user['user_id']);
