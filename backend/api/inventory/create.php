<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\InventoryController;
use App\Middleware\AuthMiddleware;

$user = AuthMiddleware::authenticate('Store Manager');
$controller = new InventoryController();

$data = json_decode(file_get_contents("php://input"), true);
$controller->createItem($data, $user['user_id']);
