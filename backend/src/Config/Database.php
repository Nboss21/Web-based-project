<?php

namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $driver = strtolower($_ENV['DB_DRIVER'] ?? 'mysql');
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? ($driver === 'pgsql' ? 5432 : 3306);
        $db   = $_ENV['DB_NAME'] ?? 'web_project_db';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';
        $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            if ($driver === 'pgsql' || $driver === 'postgres' || $driver === 'postgresql') {
                // Use pgsql driver (Neon/Postgres)
                // Accept optional DB_SSLMODE env (e.g. require)
                $sslmode = isset($_ENV['DB_SSLMODE']) ? ';sslmode=' . $_ENV['DB_SSLMODE'] : '';

                // Neon SNI compatibility: older libpq may require passing the endpoint ID
                // as an 'options' parameter. You can set DB_NEON_ENDPOINT in .env to override.
                $neonEndpoint = $_ENV['DB_NEON_ENDPOINT'] ?? null;
                if (!$neonEndpoint && $host) {
                    // derive first segment of the host (before first dot)
                    $parts = explode('.', $host);
                    $neonEndpoint = $parts[0] ?? null;
                }

                $endpointOption = '';
                if ($neonEndpoint) {
                    // options value should be endpoint=<endpoint-id>
                    $endpointOption = ";options='endpoint={$neonEndpoint}'";
                }

                $dsn = "pgsql:host={$host};port={$port};dbname={$db}{$sslmode}{$endpointOption}";
                $this->connection = new PDO($dsn, $user, $pass, $options);

                // Ensure UTF-8 client encoding (Postgres expects 'UTF8' not 'utf8mb4')
                $pgCharset = (stripos($charset, 'utf8') !== false) ? 'UTF8' : $charset;
                $this->connection->exec("SET client_encoding TO '{$pgCharset}'");
            } else {
                // Default to MySQL
                $dsn = "mysql:host={$host};dbname={$db};charset={$charset};port={$port}";
                $this->connection = new PDO($dsn, $user, $pass, $options);
            }
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
