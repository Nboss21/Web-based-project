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

    public function createRequest($data, $files, $userId) {
        $title = $this->sanitize($data['title'] ?? '');
        $description = $this->sanitize($data['description'] ?? '');
        $category = $this->sanitize($data['category'] ?? 'General');
        $priority = $data['priority'] ?? 'Medium';

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
            $stmt = $this->db->prepare("INSERT INTO maintenance_requests (user_id, title, description, category, priority, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
            $stmt->execute([$userId, $title, $description, $category, $priority]);
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

    public function getRequests($queryParams, $user) {
        $page = (int)($queryParams['page'] ?? 1);
        $limit = (int)($queryParams['limit'] ?? 10);
        $offset = ($page - 1) * $limit;

        $conditions = [];
        $params = [];

        // Role-based filtering
        if ($user['role'] === 'Student' || $user['role'] === 'Staff') {
            $conditions[] = "user_id = ?";
            $params[] = $user['user_id'];
        }

        // Filters
        if (!empty($queryParams['status'])) {
            $conditions[] = "status = ?";
            $params[] = $queryParams['status'];
        }
        if (!empty($queryParams['category'])) {
            $categories = explode(',', $queryParams['category']);
            $placeholders = implode(',', array_fill(0, count($categories), '?'));
            $conditions[] = "category IN ($placeholders)";
            foreach ($categories as $cat) $params[] = $this->sanitize($cat);
        }
        if (!empty($queryParams['priority'])) {
            $conditions[] = "priority = ?";
            $params[] = $queryParams['priority'];
        }
        if (!empty($queryParams['date_from'])) {
            $conditions[] = "created_at >= ?";
            $params[] = $queryParams['date_from'] . " 00:00:00";
        }
        if (!empty($queryParams['date_to'])) {
            $conditions[] = "created_at <= ?";
            $params[] = $queryParams['date_to'] . " 23:59:59";
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        // Total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM maintenance_requests $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Data
        $stmt = $this->db->prepare("
            SELECT r.*, u.name as submitter_name 
            FROM maintenance_requests r
            JOIN users u ON r.user_id = u.id
            $whereClause 
            ORDER BY created_at DESC 
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $requests = $stmt->fetchAll();

        Response::success("Requests fetched successfully", [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'data' => $requests
        ]);
    }

    public function getRequestDetails($id, $user) {
        // Fetch request
        $stmt = $this->db->prepare("
            SELECT r.*, u.name as submitter_name 
            FROM maintenance_requests r
            JOIN users u ON r.user_id = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $request = $stmt->fetch();

        if (!$request) {
            Response::error("Request not found", 404);
        }

        // Permission check
        if ($user['role'] !== 'Admin' && $user['role'] !== 'Technician' && $request['user_id'] != $user['user_id']) {
            Response::error("Forbidden", 403);
        }

        // Polling Support (If-Modified-Since)
        $lastModified = strtotime($request['updated_at']);
        $ifModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : false;

        if ($ifModifiedSince && $lastModified <= $ifModifiedSince) {
            http_response_code(304);
            exit;
        }

        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');

        // Fetch Images
        $stmt = $this->db->prepare("SELECT image_path FROM request_images WHERE request_id = ?");
        $stmt->execute([$id]);
        $request['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Fetch Tasks (Status Timeline)
        $stmt = $this->db->prepare("
            SELECT t.*, u.name as changed_by_name, a.name as assigned_to_name
            FROM tasks t
            LEFT JOIN users u ON t.changed_by = u.id
            LEFT JOIN users a ON t.assigned_to = a.id
            WHERE t.request_id = ?
            ORDER BY t.created_at ASC
        ");
        $stmt->execute([$id]);
        $request['timeline'] = $stmt->fetchAll();

        Response::success("Request details fetched", $request);
    }

    public function serveImage($filename, $user) {
        $stmt = $this->db->prepare("
            SELECT r.user_id 
            FROM request_images i
            JOIN maintenance_requests r ON i.request_id = r.id
            WHERE i.image_path LIKE ?
        ");
        $stmt->execute(["%$filename%"]);
        $request = $stmt->fetch();

        if (!$request) {
            Response::error("Image not found", 404);
        }

        // Permission check
        if ($user['role'] !== 'Admin' && $user['role'] !== 'Technician' && $request['user_id'] != $user['user_id']) {
            Response::error("Forbidden", 403);
        }

        $filePath = $this->uploadDir . $filename;
        if (!file_exists($filePath)) {
            Response::error("File not found on disk", 404);
        }

        // Caching headers
        $etag = md5_file($filePath);
        header("Etag: $etag");
        header("Cache-Control: public, max-age=86400"); // 24h

        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
            http_response_code(304);
            exit;
        }

        $mimeType = mime_content_type($filePath);
        header("Content-Type: $mimeType");
        header("Content-Length: " . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public function assignTechnician($id, $data, $admin) {
        $technicianId = $data['technician_id'] ?? null;
        $dueDate = $data['due_date'] ?? null;
        $notes = $this->sanitize($data['notes'] ?? '');

        // 1. Permission Check
        if ($admin['role'] !== 'Admin' && $admin['role'] !== 'Manager') {
            Response::error("Forbidden: Admin or Manager access required", 403);
        }

        if (!$technicianId) {
            Response::error("Technician ID is required");
        }

        // 2. Validate Request
        $stmt = $this->db->prepare("SELECT * FROM maintenance_requests WHERE id = ?");
        $stmt->execute([$id]);
        $request = $stmt->fetch();

        if (!$request) {
            Response::error("Request not found", 404);
        }

        if ($request['status'] !== 'Pending' && $request['status'] !== 'Assigned') {
            Response::error("Request cannot be assigned in current status: " . $request['status']);
        }

        // 3. Validate Technician
        $stmt = $this->db->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'Technician' AND status = 'Active'");
        $stmt->execute([$technicianId]);
        $technician = $stmt->fetch();

        if (!$technician) {
            Response::error("Technician not found or inactive");
        }

        $this->db->beginTransaction();
        try {
            $previousTechnicianId = $request['assigned_to'];

            // 4. Re-assignment Logic: Archive old task if exists
            if ($previousTechnicianId) {
                $stmt = $this->db->prepare("UPDATE tasks SET is_active = FALSE WHERE request_id = ? AND is_active = TRUE");
                $stmt->execute([$id]);

                // Notify previous technician
                $this->notify($previousTechnicianId, "Request #$id has been re-assigned to someone else.", 'reassignment');
            }

            // 5. Update Maintenance Request
            $stmt = $this->db->prepare("
                UPDATE maintenance_requests 
                SET assigned_to = ?, status = 'Assigned', assigned_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$technicianId, $id]);

            // 6. Create Task Record
            $stmt = $this->db->prepare("
                INSERT INTO tasks (request_id, assigned_to, changed_by, status, notes, due_date, is_active) 
                VALUES (?, ?, ?, 'Assigned', ?, ?, TRUE)
            ");
            $stmt->execute([$id, $technicianId, $admin['user_id'], $notes, $dueDate]);

            // 7. Notifications
            $notifService = new \App\Services\NotificationService();
            // Notify Technician
            $notifService->createNotification($technicianId, 'task_assigned', 'task', $id, ['id' => $id]);
            // Notify Submitter
            $notifService->createNotification($request['user_id'], 'status_updated', 'request', $id, ['id' => $id, 'status' => 'Assigned']);
            // Notify Admin
            $notifService->notifyAdmins('status_updated', 'request', $id, ['id' => $id, 'status' => 'Assigned']);

            // 8. Audit Log
            $this->logAudit($admin['user_id'], "Assigned request to technician $technicianId", 'request', $id, json_encode($data));

            $this->db->commit();
            Response::success("Request assigned successfully to " . $technician['name']);

        } catch (Exception $e) {
            $this->db->rollBack();
            Response::error("Failed to assign request: " . $e->getMessage(), 500);
        }
    }

    private function notify($userId, $message, $type) {
        $stmt = $this->db->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $message, $type]);
    }

    private function logAudit($userId, $action, $entityType, $entityId, $details = null) {
        $stmt = $this->db->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $entityType, $entityId, $details]);
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
        $notifService = new \App\Services\NotificationService();
        
        // 1. Notify Admins
        $notifService->notifyAdmins('new_request', 'request', $requestId, ['title' => $title]);

        // 2. Notify Submitting User (Confirmation)
        $notifService->createNotification($userId, 'request_confirmation', 'request', $requestId, ['title' => $title]);
    }
}
