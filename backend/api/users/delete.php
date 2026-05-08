<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

use App\Utils\Input;

$user = AuthMiddleware::authenticate('Admin');
$controller = new UserController();

$id = $_GET['id'] ?? null;
$hard = isset($_GET['hard']) && $_GET['hard'] == '1';

if (!$id) Response::error("User ID is required");

// Require POST to perform deletes to avoid accidental GET deletes
Input::requirePost();

$controller->deleteUser($id, $user, $hard);
