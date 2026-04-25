<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Utils\Response;

// Protected: Only Admin can access
$user = AuthMiddleware::authenticate('Admin');

Response::success("Welcome, Admin!", [
    'user' => $user
]);
