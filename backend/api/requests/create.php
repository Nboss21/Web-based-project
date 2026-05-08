<?php

require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\RequestController;
use App\Middleware\AuthMiddleware;
use App\Utils\Input;
use App\Utils\Response;

// Protected: Requires valid JWT
$user = AuthMiddleware::authenticate();

$controller = new RequestController();

// If Content-Type is JSON, require POST and pass JSON body as simple form
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
	Input::requirePost();
	$data = Input::getJsonBody();
	if (!is_array($data)) Response::error('Invalid or missing JSON body', 400);
	$controller->createRequest($data, [], $user['user_id']);
} else {
	// Assume multipart/form-data for files
	$controller->createRequest($_POST, $_FILES, $user['user_id']);
}
