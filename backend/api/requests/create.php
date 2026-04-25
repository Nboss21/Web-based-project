<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\RequestController;
use App\Middleware\AuthMiddleware;

// Protected: Requires valid JWT
$user = AuthMiddleware::authenticate();

$controller = new RequestController();

// Use $_POST for text fields and $_FILES for images (multipart/form-data)
$controller->createRequest($_POST, $_FILES, $user['user_id']);
