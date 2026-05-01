<?php

require_once __DIR__ . '/../../../bootstrap.php';

use App\Controllers\NotificationController;
use App\Middleware\AuthMiddleware;

$user = AuthMiddleware::authenticate();
$controller = new NotificationController();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $controller->getPreferences($user['user_id']);
} else {
    $data = json_decode(file_get_contents("php://input"), true);
    $controller->updatePreferences($user['user_id'], $data);
}
