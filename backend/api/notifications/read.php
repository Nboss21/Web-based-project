<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\NotificationController;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

$user = AuthMiddleware::authenticate();
$controller = new NotificationController();

$id = $_GET['id'] ?? null;
if (!$id) Response::error("Notification ID is required");

$controller->markAsRead($user['user_id'], $id);
