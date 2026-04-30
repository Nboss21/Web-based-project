<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

$user = AuthMiddleware::authenticate('Admin');
$controller = new UserController();

$id = $_GET['id'] ?? null;
if (!$id) Response::error("User ID is required");

$data = json_decode(file_get_contents("php://input"), true);
$controller->updateUser($id, $data, $user);
