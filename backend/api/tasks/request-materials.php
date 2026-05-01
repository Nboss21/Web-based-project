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

$data = json_decode(file_get_contents("php://input"), true);
$controller->requestMaterials($id, $data, $user['user_id']);
