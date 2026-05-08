<?php

namespace App\Utils;

class Response {
    private static function isListArray($value) {
        if (!is_array($value)) {
            return false;
        }

        return $value === [] || array_keys($value) === range(0, count($value) - 1);
    }

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
        if (self::isListArray($data)) {
            self::send(['message' => $message, 'data' => $data], 200);
            return;
        }

        self::send(array_merge(['message' => $message], $data), 200);
    }
}
