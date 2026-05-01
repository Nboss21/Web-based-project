<?php
require_once __DIR__ . '/../bootstrap.php';

use App\Config\Database;

echo "Testing DB connection...\n";
try {
    $db = Database::getInstance()->getConnection();
    // Simple query to validate connection
    $stmt = $db->query('SELECT version() AS version');
    $row = $stmt->fetch();
    echo "Connected. Server version: " . ($row['version'] ?? 'unknown') . "\n";
    exit(0);
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(2);
}
