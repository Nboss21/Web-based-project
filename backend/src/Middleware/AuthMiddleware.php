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

        // Normalize required roles to an array for flexible checks. Accepts:
        // - null (no role required)
        // - string (single role)
        // - comma-separated string ("Admin,Technician")
        // - array of roles
        if ($requiredRole) {
            $roles = is_array($requiredRole) ? $requiredRole : array_map('trim', explode(',', $requiredRole));

            // Allow 'Admin' as a superuser to bypass role restrictions
            if ($decoded['role'] !== 'Admin' && !in_array($decoded['role'], $roles, true)) {
                Response::error("Forbidden: Insufficient permissions", 403);
            }
        }

        return $decoded;
    }
}
