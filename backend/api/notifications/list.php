<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\NotificationController;
use App\Middleware\AuthMiddleware;

$user = AuthMiddleware::authenticate();
$controller = new NotificationController();

$controller->listNotifications($user['user_id'], $_GET);
