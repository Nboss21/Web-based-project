<?php

namespace App\Services;

use App\Config\Database;
use PDO;

class NotificationService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a notification for a user
     */
    public function createNotification($userId, $type, $entityType = null, $entityId = null, $extraData = []) {
        $templates = [
            'new_request' => [
                'title' => 'New Maintenance Request',
                'message' => "A new request '#{title}' has been submitted."
            ],
            'task_assigned' => [
                'title' => 'New Task Assigned',
                'message' => "You have been assigned to task ##{id}."
            ],
            'status_updated' => [
                'title' => 'Status Updated',
                'message' => "Request ##{id} status changed to #{status}."
            ],
            'low_stock' => [
                'title' => 'Low Stock Alert',
                'message' => "Item '#{name}' is below reorder level."
            ],
            'material_request' => [
                'title' => 'New Material Request',
                'message' => "Technician ##{tech_id} requested materials for task ##{task_id}."
            ],
            'material_approved' => [
                'title' => 'Material Request Approved',
                'message' => "Your material request for task ##{task_id} has been approved."
            ],
            'user_registered' => [
                'title' => 'New User Registered',
                'message' => "A new user #{name} has joined the platform."
            ]
        ];

        $template = $templates[$type] ?? ['title' => 'Notification', 'message' => 'You have a new update.'];
        
        $title = $template['title'];
        $message = $template['message'];

        // Replace placeholders in message
        foreach ($extraData as $key => $value) {
            $message = str_replace("#{ $key }", $value, $message);
            $message = str_replace("#{$key}", $value, $message);
        }

        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, type, title, message, related_entity_type, related_entity_id, metadata)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $metadata = !empty($extraData) ? json_encode($extraData) : null;

        return $stmt->execute([
            $userId, 
            $type, 
            $title, 
            $message, 
            $entityType, 
            $entityId, 
            $metadata
        ]);
    }

    /**
     * Notify all admins
     */
    public function notifyAdmins($type, $entityType = null, $entityId = null, $extraData = []) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE role = 'Admin'");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($admins as $adminId) {
            $this->createNotification($adminId, $type, $entityType, $entityId, $extraData);
        }
    }
    
    /**
     * Notify Store Managers
     */
    public function notifyStoreManagers($type, $entityType = null, $entityId = null, $extraData = []) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE role = 'Store Manager' OR role = 'Manager'");
        $stmt->execute();
        $managers = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($managers as $id) {
            $this->createNotification($id, $type, $entityType, $entityId, $extraData);
        }
    }
}
