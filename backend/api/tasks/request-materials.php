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

use App\Utils\Input;

Input::requirePost();
$data = Input::getJsonBody();
if (!is_array($data)) Response::error('Invalid or missing JSON body', 400);

$controller->requestMaterials($id, $data, $user['user_id']);
