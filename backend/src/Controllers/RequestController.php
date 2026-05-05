<?php

namespace App\Controllers;

use App\Config\Database;
use App\Utils\Response;
use PDO;
use Exception;

class RequestController {
    private $db;
    private $uploadDir = __DIR__ . '/../../uploads/requests/';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    public function getRequests($queryParams, $user) {
        $page = (int)($queryParams['page'] ?? 1);
        $limit = (int)($queryParams['limit'] ?? 10);
        $offset = ($page - 1) * $limit;

        $conditions = [];
        $params = [];

        // Determine visibility based on role
        if ($user['role'] === 'Student' || $user['role'] === 'Staff') {
            $conditions[] = "user_id = ?";
            $params[] = $user['user_id'];
        }
        
        $whereClause = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM maintenance_requests $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT r.*, u.name as user_name 
            FROM maintenance_requests r
            LEFT JOIN users u ON r.user_id = u.id
            $whereClause
            ORDER BY r.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $requests = $stmt->fetchAll();

        // Fetch images for each request
        foreach ($requests as &$req) {
            $imgStmt = $this->db->prepare("SELECT id, image_path FROM request_images WHERE request_id = ?");
            $imgStmt->execute([$req['id']]);
            $req['images'] = $imgStmt->fetchAll();
        }

        Response::success("Requests fetched successfully", [
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit,
            'data' => $requests
        ]);
    }

    public function createRequest($data, $files, $userId) {
        $title = $this->sanitize($data['title'] ?? '');
        $description = $this->sanitize($data['description'] ?? '');

        // 1. Validation
        if (empty($title) || empty($description)) {
            Response::error("Title and description are required");
        }

        // 2. Duplicate Check (5 minutes)
        if ($this->isDuplicate($userId, $title, $description)) {
            Response::error("Duplicate request detected. Please wait 5 minutes before submitting the same issue again.", 429);
        }

        $this->db->beginTransaction();
        try {
            // 3. Create Maintenance Request
            $stmt = $this->db->prepare("INSERT INTO maintenance_requests (user_id, title, description, status) VALUES (?, ?, ?, 'Pending')");
            $stmt->execute([$userId, $title, $description]);
            $requestId = $this->db->lastInsertId();

            // 4. File Handling
            $uploadedFiles = $this->handleFileUploads($files, $userId, $requestId);

            // 5. Notifications
            $this->triggerNotifications($userId, $requestId, $title);

            $this->db->commit();

            Response::success("Request submitted successfully", [
                'request_id' => $requestId,
                'files' => $uploadedFiles,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            Response::error("Failed to process request: " . $e->getMessage(), 500);
        }
    }

    private function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    private function isDuplicate($userId, $title, $description) {
        $stmt = $this->db->prepare("
            SELECT id FROM maintenance_requests 
            WHERE user_id = ? AND title = ? AND description = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $stmt->execute([$userId, $title, $description]);
        return $stmt->fetch() !== false;
    }

    private function handleFileUploads($files, $userId, $requestId) {
        $storedPaths = [];
        
        if (empty($files['images'])) {
            return [];
        }

        // Handle multiple files if sent as an array, otherwise wrap single file in array
        $images = $files['images'];
        if (!is_array($images['name'])) {
            $images = [
                'name' => [$images['name']],
                'type' => [$images['type']],
                'tmp_name' => [$images['tmp_name']],
                'error' => [$images['error']],
                'size' => [$images['size']]
            ];
        }

        for ($i = 0; $i < count($images['name']); $i++) {
            if ($images['error'][$i] !== UPLOAD_ERR_OK) continue;

            $fileName = $images['name'][$i];
            $fileSize = $images['size'][$i];
            $fileTmp = $images['tmp_name'][$i];
            $fileType = $images['type'][$i];

            // Validate Type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Invalid file type: $fileName. Only JPG, PNG, and GIF are allowed.");
            }

            // Validate Size (5MB)
            if ($fileSize > 5 * 1024 * 1024) {
                throw new Exception("File too large: $fileName. Max size is 5MB.");
            }

            // Generate Unique Filename
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = time() . "_" . $userId . "_" . bin2hex(random_bytes(8)) . "." . $extension;
            $destination = $this->uploadDir . $newFileName;

            if (move_uploaded_file($fileTmp, $destination)) {
                $dbPath = 'uploads/requests/' . $newFileName;
                
                // Save to DB
                $stmt = $this->db->prepare("INSERT INTO request_images (request_id, image_path) VALUES (?, ?)");
                $stmt->execute([$requestId, $dbPath]);
                
                $storedPaths[] = $dbPath;
            } else {
                throw new Exception("Failed to save file: $fileName");
            }
        }

        return $storedPaths;
    }

    private function triggerNotifications($userId, $requestId, $title) {
        // 1. Notify Admins
        $adminStmt = $this->db->prepare("SELECT id FROM users WHERE role = 'Admin'");
        $adminStmt->execute();
        $admins = $adminStmt->fetchAll();

        foreach ($admins as $admin) {
            $stmt = $this->db->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)");
            $stmt->execute([
                $admin['id'], 
                "New maintenance request #$requestId submitted: $title", 
                'new_request'
            ]);
        }

        // 2. Notify Submitting User
        $stmt = $this->db->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)");
        $stmt->execute([
            $userId, 
            "Your request #$requestId '$title' has been received and is pending review.", 
            'request_confirmation'
        ]);
    }
}
