<?php
// scripts/seed_admin.php
// Usage (from backend/): php scripts/seed_admin.php --email=admin@campus.edu --password=password --name="Admin User"

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

$opts = getopt('', ['email::', 'password::', 'name::']);

$email = $opts['email'] ?? 'admin@campus.edu';
$password = $opts['password'] ?? 'password';
$name = $opts['name'] ?? 'Admin User';

echo "Seeding admin user...\n";

try {
    // Ensure environment variables are loaded (Dotenv may be configured elsewhere)
    if (file_exists(__DIR__ . '/../.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    }

    $db = Database::getInstance()->getConnection();

    // Check if user exists
    $stmt = $db->prepare('SELECT id, email FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing) {
        echo "User with email {$email} already exists (id={$existing['id']}).\n";
        exit(0);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    $insert = $db->prepare('INSERT INTO users (name, email, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
    $ok = $insert->execute([$name, $email, $hash, 'Admin', 'Active']);

    if ($ok) {
        $id = $db->lastInsertId();
        echo "Admin user created: id={$id}, email={$email}, password={$password}\n";
        echo "IMPORTANT: Change the password after first login.\n";
        exit(0);
    } else {
        echo "Failed to create admin user.\n";
        exit(2);
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(3);
}
