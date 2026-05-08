<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\InventoryController;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;
use App\Utils\Input;

$user = AuthMiddleware::authenticate('Store Manager');
$controller = new InventoryController();

$id = $_GET['id'] ?? null;
if (!$id) Response::error("Item ID is required");

Input::requirePost();
$data = Input::getJsonBody();
if (!is_array($data)) Response::error('Invalid or missing JSON body', 400);

$controller->adjustStock($id, $data, $user['user_id']);
