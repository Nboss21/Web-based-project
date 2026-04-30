<?php
require_once __DIR__ . '/../bootstrap.php';

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();

    $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'User',
    specialization VARCHAR(100) DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'Active',
    notification_preferences JSON DEFAULT NULL,
    last_login TIMESTAMP DEFAULT NULL,
    failed_attempts INT DEFAULT 0,
    is_locked BOOLEAN DEFAULT FALSE,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
SQL;

    $db->exec($sql);
    echo "Users table ensured.\n";
    exit(0);
} catch (Exception $e) {
    echo "Failed to create users table: " . $e->getMessage() . "\n";
    exit(1);
}
