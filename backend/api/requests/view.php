<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\RequestController;
use App\Middleware\AuthMiddleware;

$user = AuthMiddleware::authenticate();
$controller = new RequestController();

$id = $_GET['id'] ?? null;
if (!$id) {
    App\Utils\Response::error("Request ID is required");
}

$controller->getRequestDetails($id, $user);
