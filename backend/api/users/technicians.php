<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;

$user = AuthMiddleware::authenticate();
$controller = new UserController();

$controller->getTechnicians($user);
