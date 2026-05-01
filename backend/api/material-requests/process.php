<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\InventoryController;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

$user = AuthMiddleware::authenticate('Store Manager');
$controller = new InventoryController();

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null; // approve or reject

if (!$id || !$action) Response::error("Request ID and action are required");

$data = json_decode(file_get_contents("php://input"), true);
$controller->processRequest($id, $action, $data, $user['user_id']);
