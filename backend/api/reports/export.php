<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\ReportController;
use App\Middleware\AuthMiddleware;

$user = AuthMiddleware::authenticate('Admin');
$controller = new ReportController();

$controller->exportCSV($_GET, $user);
