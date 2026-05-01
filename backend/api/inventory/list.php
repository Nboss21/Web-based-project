<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\InventoryController;
use App\Middleware\AuthMiddleware;

$user = AuthMiddleware::authenticate();
$controller = new InventoryController();

$controller->listItems($_GET, $user);
