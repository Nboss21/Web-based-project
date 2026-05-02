<?php
// scripts/reset_admin_password.php
require_once __DIR__ . '/../bootstrap.php';

use App\Config\Database;

$opts = getopt('', ['email::', 'password::']);
$email = $opts['email'] ?? 'admin@campus.edu';
$password = $opts['password'] ?? 'password';

try {
    if (file_exists(__DIR__ . '/../.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    }

    $db = Database::getInstance()->getConnection();
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
    $ok = $stmt->execute([$hash, $email]);
    if ($ok) {
        echo "Password for {$email} updated to '{$password}'\n";
        exit(0);
    } else {
        echo "Failed to update password.\n";
        exit(2);
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(3);
}
