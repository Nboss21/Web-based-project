<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\RequestController;
use App\Middleware\AuthMiddleware;

$user = AuthMiddleware::authenticate();
$controller = new RequestController();

$controller->getRequests($_GET, $user);
