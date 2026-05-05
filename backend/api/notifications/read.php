<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Middleware\AuthMiddleware;
use App\Config\Database;
use App\Utils\Response;

$user = AuthMiddleware::authenticate();
$db = Database::getInstance()->getConnection();

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $db->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user['user_id']]);
} else {
    $stmt = $db->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
    $stmt->execute([$user['user_id']]);
}

Response::success("Notifications marked as read");