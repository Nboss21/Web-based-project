<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

$user = AuthMiddleware::authenticate('Admin');
$controller = new UserController();

$id = $_GET['id'] ?? null;
$hard = isset($_GET['hard']) && $_GET['hard'] == '1';

if (!$id) Response::error("User ID is required");

$controller->deleteUser($id, $user, $hard);
