<?php

namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $charset = 'utf8mb4';

        // If a Neon / Heroku-style DATABASE_URL or NEON_DATABASE_URL is present,
        // parse it and use the pgsql driver. Otherwise fall back to MySQL.
        $databaseUrl = $_ENV['NEON_DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? null;
        if ($databaseUrl) {
            $parts = parse_url($databaseUrl);
            if ($parts === false) {
                throw new PDOException('Invalid NEON_DATABASE_URL');
            }

            $host = $parts['host'] ?? 'localhost';
            $port = $parts['port'] ?? 5432;
            $user = $parts['user'] ?? '';
            $pass = $parts['pass'] ?? '';
            $db   = isset($parts['path']) ? ltrim($parts['path'], '/') : '';

            $query = [];
            if (isset($parts['query'])) {
                parse_str($parts['query'], $query);
            }
            $sslmode = $query['sslmode'] ?? 'require';

            $dsn = "pgsql:host=$host;port=$port;dbname=$db";
            if ($sslmode) {
                $dsn .= ";sslmode=$sslmode";
            }
            
            // Extract endpoint ID for Neon SNI (format: ep-xxxxx-pooler.c-2.us-east-1.aws.neon.tech)
            if (strpos($host, 'neon.tech') !== false) {
                if (preg_match('/^(ep-[a-z0-9\-]+)-pooler/', $host, $matches)) {
                    $endpointId = $matches[1];
                    // Pass endpoint ID as connection option
                    $dsn .= ";options=endpoint=" . $endpointId;
                }
            }
        } else {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $db   = $_ENV['DB_NAME'] ?? 'web_project_db';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        }
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}
