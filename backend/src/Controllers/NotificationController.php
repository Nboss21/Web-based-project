<?php

namespace App\Controllers;

use App\Config\Database;
use App\Utils\Response;
use PDO;

class NotificationController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function listNotifications($userId, $queryParams) {
        $limit = (int)($queryParams['limit'] ?? 20);
        $offset = (int)($queryParams['offset'] ?? 0);
        $isRead = isset($queryParams['is_read']) ? (bool)$queryParams['is_read'] : null;
        $type = $queryParams['type'] ?? null;

        $conditions = ["user_id = ?"];
        $params = [$userId];

        if ($isRead !== null) {
            $conditions[] = "is_read = ?";
            $params[] = $isRead ? 1 : 0;
        }

        if ($type) {
            $conditions[] = "type = ?";
            $params[] = $type;
        }

        $whereClause = "WHERE " . implode(" AND ", $conditions);

        // Fetch Notifications
        $stmt = $this->db->prepare("
            SELECT * FROM notifications 
            $whereClause 
            ORDER BY created_at DESC 
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $notifications = $stmt->fetchAll();

        // Get unread count
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $stmtCount->execute([$userId]);
        $unreadCount = $stmtCount->fetchColumn();

        Response::success("Notifications fetched", [
            'unread_count' => $unreadCount,
            'data' => $notifications
        ]);
    }

    public function markAsRead($userId, $id) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        Response::success("Notification marked as read");
    }

    public function markAllAsRead($userId) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
        $stmt->execute([$userId]);
        Response::success("All notifications marked as read");
    }

    public function deleteNotification($userId, $id) {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        Response::success("Notification deleted");
    }

    public function getPreferences($userId) {
        $stmt = $this->db->prepare("SELECT notification_preferences FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $prefs = $stmt->fetchColumn();
        Response::success("Preferences fetched", ['preferences' => json_decode($prefs)]);
    }

    public function updatePreferences($userId, $data) {
        $prefs = json_encode($data);
        $stmt = $this->db->prepare("UPDATE users SET notification_preferences = ? WHERE id = ?");
        $stmt->execute([$prefs, $userId]);
        Response::success("Preferences updated");
    }
}
