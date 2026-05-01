<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\TaskController;
use App\Middleware\AuthMiddleware;

$user = AuthMiddleware::authenticate('Technician');
$controller = new TaskController();

$controller->getMyTasks($user['user_id']);
