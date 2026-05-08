<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Utils\Input;
use App\Utils\Response;

$user = AuthMiddleware::authenticate('Admin');
$controller = new UserController();

Input::requirePost();
$data = Input::getJsonBody();
if (!is_array($data)) Response::error('Invalid or missing JSON body', 400);

$controller->createUser($data, $user['user_id']);
