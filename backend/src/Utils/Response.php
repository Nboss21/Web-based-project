<?php

namespace App\Utils;

class Response {
    public static function send($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    public static function error($message, $statusCode = 400) {
        self::send(['error' => $message], $statusCode);
    }

    public static function success($message, $data = []) {
        self::send(array_merge(['message' => $message], $data), 200);
    }
}
