<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\InventoryController;
use App\Middleware\AuthMiddleware;

$user = AuthMiddleware::authenticate('Store Manager');
$controller = new InventoryController();

$controller->getPendingRequests();
