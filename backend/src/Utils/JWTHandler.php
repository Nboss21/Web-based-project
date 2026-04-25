<?php

namespace App\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTHandler {
    private static $secret;
    private static $expiration;

    private static function init() {
        self::$secret = $_ENV['JWT_SECRET'] ?? 'default_secret';
        self::$expiration = (int)($_ENV['JWT_EXPIRATION'] ?? 86400);
    }

    public static function encode($payload) {
        self::init();
        $payload['iat'] = time();
        $payload['exp'] = time() + self::$expiration;
        
        return JWT::encode($payload, self::$secret, 'HS256');
    }

    public static function decode($token) {
        self::init();
        try {
            return (array) JWT::decode($token, new Key(self::$secret, 'HS256'));
        } catch (Exception $e) {
            return null;
        }
    }
}
