<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\InventoryController;
use App\Middleware\AuthMiddleware;
use App\Utils\Input;
use App\Utils\Response;

$user = AuthMiddleware::authenticate('Store Manager');
$controller = new InventoryController();

Input::requirePost();
$data = Input::getJsonBody();
if (!is_array($data)) Response::error('Invalid or missing JSON body', 400);

$controller->createItem($data, $user['user_id']);
