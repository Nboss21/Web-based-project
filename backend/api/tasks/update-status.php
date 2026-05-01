<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\TaskController;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

$user = AuthMiddleware::authenticate('Technician');
$controller = new TaskController();

$id = $_GET['id'] ?? null;
if (!$id) {
    Response::error("Task ID is required");
}

// Multipart/form-data support for photos
$controller->updateStatus($id, $_POST, $_FILES, $user['user_id']);
