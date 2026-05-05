<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Middleware\AuthMiddleware;
use App\Config\Database;
use App\Utils\Response;

$user = AuthMiddleware::authenticate();
$db = Database::getInstance()->getConnection();

$limit = (int)($_GET['limit'] ?? 10);
$userId = $user['user_id'];

$stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
$stmt->execute([$userId]);
$unread = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit");
$stmt->execute([$userId]);
$data = $stmt->fetchAll();

Response::success("Notifications fetched", ['unread_count' => $unread, 'data' => $data]);