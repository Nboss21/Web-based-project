<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\RequestController;
use App\Middleware\AuthMiddleware;

$user = AuthMiddleware::authenticate();
$controller = new RequestController();

$filename = $_GET['file'] ?? null;
if (!$filename) {
    App\Utils\Response::error("Filename is required");
}

// Sanitize filename to prevent directory traversal
$filename = basename($filename);

$controller->serveImage($filename, $user);
