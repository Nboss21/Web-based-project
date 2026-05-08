<?php

namespace App\Utils;

class Input {
    /**
     * Parse JSON request body and return as array or null on failure
     */
    public static function getJsonBody() {
        $raw = file_get_contents('php://input');
        if (!$raw) return null;
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) return null;
        return $data;
    }

    /**
     * Require a JSON body and return it as array; sends error response on failure
     */
    public static function requireJsonBody() {
        $data = self::getJsonBody();
        if (!is_array($data)) {
            \App\Utils\Response::error('Invalid or missing JSON body', 400);
        }
        return $data;
    }

    /**
     * Require that request method is POST
     */
    public static function requirePost() {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            \App\Utils\Response::error('Invalid request method; POST expected', 405);
        }
    }
}
