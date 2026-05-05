<?php

namespace App\Middleware;

use App\Utils\JWTHandler;
use App\Utils\Response;

class AuthMiddleware {
    public static function authenticate($requiredRole = null) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            Response::error("Authorization token missing", 401);
        }

        $token = $matches[1];
        $decoded = JWTHandler::decode($token);

        if (!$decoded) {
            Response::error("Invalid or expired token", 401);
        }

        if ($requiredRole) {
            $roles = is_array($requiredRole) ? $requiredRole : [$requiredRole];
            if (!in_array($decoded['role'], $roles) && $decoded['role'] !== 'Admin') {
                Response::error("Forbidden: Insufficient permissions", 403);
            }
        }

        return $decoded;
    }
}
