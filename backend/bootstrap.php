<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// CORS Headers - Configure based on environment
if (php_sapi_name() !== 'cli') {
    // Get allowed origins from environment, default to * for development
    $allowedOrigins = explode(',', trim($_ENV['CORS_ORIGINS'] ?? '*'));
    $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    // Check if origin is allowed
    $isAllowed = in_array('*', $allowedOrigins) || in_array($requestOrigin, $allowedOrigins);
    
    if ($isAllowed) {
        header('Access-Control-Allow-Origin: ' . ($requestOrigin ?: '*'));
    }
    
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 86400");

    if (($_SERVER['REQUEST_METHOD'] ?? null) == 'OPTIONS') {
        exit;
    }
}

// Global error handling
set_exception_handler(function($e) {
    App\Utils\Response::error($e->getMessage(), 500);
});
