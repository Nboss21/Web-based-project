<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;
use App\Utils\Input;

$user = AuthMiddleware::authenticate('Admin');
$controller = new UserController();

$id = $_GET['id'] ?? null;
if (!$id) Response::error("User ID is required");

Input::requirePost();
$data = Input::getJsonBody();
if (!is_array($data)) Response::error('Invalid or missing JSON body', 400);

$controller->updateUser($id, $data, $user);
