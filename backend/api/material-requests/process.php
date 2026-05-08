<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\InventoryController;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;
use App\Utils\Input;

$user = AuthMiddleware::authenticate('Store Manager');
$controller = new InventoryController();

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null; // approve or reject

if (!$id || !$action) Response::error("Request ID and action are required");

Input::requirePost();
$data = Input::getJsonBody();
if (!is_array($data)) $data = [];

$controller->processRequest($id, $action, $data, $user['user_id']);
