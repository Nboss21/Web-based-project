<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\InventoryController;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

$user = AuthMiddleware::authenticate('Store Manager');
$controller = new InventoryController();

$id = $_GET['id'] ?? null;
if (!$id) Response::error("Item ID is required");

$data = json_decode(file_get_contents("php://input"), true);
$controller->updateItem($id, $data, $user['user_id']);
