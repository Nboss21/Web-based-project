<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\TaskController;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;
use App\Utils\Input;

$user = AuthMiddleware::authenticate('Technician');
$controller = new TaskController();

$id = $_GET['id'] ?? null;
if (!$id) {
    Response::error("Task ID is required");
}

// Multipart/form-data support for photos
// If JSON payload is sent
if (($_SERVER['CONTENT_TYPE'] ?? '') === 'application/json') {
    Input::requirePost();
    $data = Input::getJsonBody();
    if (!is_array($data)) Response::error('Invalid or missing JSON body', 400);
    $controller->updateStatus($id, $data, [], $user['user_id']);
} else {
    $controller->updateStatus($id, $_POST, $_FILES, $user['user_id']);
}
