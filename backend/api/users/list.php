<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;

$user = AuthMiddleware::authenticate('Admin');
$controller = new UserController();

$controller->listUsers($_GET, $user);
