<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\RequestController;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

$user = AuthMiddleware::authenticate();
$controller = new RequestController();

$id = $_GET['id'] ?? null;
if (!$id) {
    Response::error("Request ID is required");
}

$data = json_decode(file_get_contents("php://input"), true);
$controller->assignTechnician($id, $data, $user);
