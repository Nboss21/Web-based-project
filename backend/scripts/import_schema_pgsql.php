<?php
// Import PostgreSQL schema file into the configured DB using PDO
require_once __DIR__ . '/../bootstrap.php';

use App\Config\Database;

$schemaFile = __DIR__ . '/../database/schema.pgsql';
if (!file_exists($schemaFile)) {
    echo "Schema file not found: $schemaFile\n";
    exit(2);
}

try {
    $db = Database::getInstance()->getConnection();
    $lines = file($schemaFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    $statements = [];
    $inCreate = false;
    $buffer = '';

    foreach ($lines as $line) {
        $trim = trim($line);
        if ($trim === '' || strpos($trim, '--') === 0) continue; // skip comments/empty
        if (preg_match('/^\\\\.*/', $trim)) continue; // skip psql meta-commands like \c
        if (preg_match('/^CREATE\s+DATABASE/i', $trim)) continue;

        if (!$inCreate && preg_match('/^CREATE\s+TABLE/i', $trim)) {
            $inCreate = true;
            $buffer = $trim . "\n";
            // if ends on same line with );
            if (preg_match('/\)\s*;?$/', $trim)) {
                $statements[] = $buffer;
                $buffer = '';
                $inCreate = false;
            }
            continue;
        }

        if ($inCreate) {
            $buffer .= $trim . "\n";
            if (preg_match('/\)\s*;?$/', $trim)) {
                $statements[] = $buffer;
                $buffer = '';
                $inCreate = false;
            }
            continue;
        }
    }

    if (empty($statements)) {
        throw new Exception('No CREATE TABLE statements found in schema file.');
    }

    @mkdir(__DIR__ . '/../logs', 0755, true);
    file_put_contents(__DIR__ . '/../logs/clean_schema_extracted.sql', implode("\n\n", $statements));

    foreach ($statements as $stmt) {
        $db->exec($stmt);
    }

    echo "Schema imported successfully.\n";
    exit(0);
} catch (Exception $e) {
    echo "Import failed: " . $e->getMessage() . "\n";
    exit(3);
}
