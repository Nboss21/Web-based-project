<?php
// Import full PostgreSQL schema by extracting CREATE TABLE blocks and executing them
require_once __DIR__ . '/../bootstrap.php';
use App\Config\Database;

$schemaFile = __DIR__ . '/../database/schema.pgsql';
if (!file_exists($schemaFile)) {
    echo "Schema file not found: $schemaFile\n";
    exit(2);
}

$sql = file_get_contents($schemaFile);
// Remove CREATE DATABASE and psql meta-commands
$sql = preg_replace('/CREATE\s+DATABASE[\s\S]*?;\s*/i', '', $sql);
$sql = preg_replace('/^\\\\.*$/m', '', $sql);

// Match CREATE TABLE ... ); blocks (non-greedy until first closing ); )
preg_match_all('/CREATE\s+TABLE[\s\S]*?\)\s*;/i', $sql, $matches);
$tables = $matches[0] ?? [];

if (empty($tables)) {
    echo "No CREATE TABLE statements found to import.\n";
    exit(1);
}

try {
    $db = Database::getInstance()->getConnection();
    foreach ($tables as $stmt) {
        $clean = trim($stmt);
        if ($clean === '') continue;
        echo "Executing: " . substr($clean, 0, 60) . "...\n";
        $db->exec($clean);
    }
    echo "All tables imported successfully.\n";
    exit(0);
} catch (Exception $e) {
    echo "Import failed: " . $e->getMessage() . "\n";
    exit(3);
}
