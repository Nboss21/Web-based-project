<?php

namespace App\Controllers;

use App\Config\Database;
use App\Utils\Response;
use App\Services\NotificationService;
use PDO;
use Exception;

class UserController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function listUsers($queryParams, $admin) {
        if ($admin['role'] !== 'Admin' && $admin['role'] !== 'Super Admin') {
            Response::error("Forbidden", 403);
        }

        $page = (int)($queryParams['page'] ?? 1);
        $limit = (int)($queryParams['limit'] ?? 10);
        $offset = ($page - 1) * $limit;

        $conditions = ["status != 'Deleted'"];
        $params = [];

        if (!empty($queryParams['role'])) {
            $conditions[] = "role = ?";
            $params[] = $queryParams['role'];
        }

        if (!empty($queryParams['status'])) {
            $conditions[] = "status = ?";
            $params[] = $queryParams['status'];
        }

        if (!empty($queryParams['search'])) {
            $conditions[] = "(name LIKE ? OR email LIKE ?)";
            $params[] = "%" . $queryParams['search'] . "%";
            $params[] = "%" . $queryParams['search'] . "%";
        }

        $whereClause = "WHERE " . implode(" AND ", $conditions);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM users $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT id, name, email, role, specialization, status, last_login, is_locked, created_at 
            FROM users 
            $whereClause 
            ORDER BY created_at DESC 
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        Response::success("Users fetched", [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'data' => $users
        ]);
    }

    public function createUser($data, $adminId) {
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $role = $data['role'] ?? 'User';
        $password = $data['password'] ?? bin2hex(random_bytes(4)); // Temp password if not provided

        if (!$name || !$email) Response::error("Name and email are required");

        // Validate uniqueness
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) Response::error("Email already exists");

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

        $stmt = $this->db->prepare("INSERT INTO users (name, email, password_hash, role, created_by) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $hash, $role, $adminId])) {
            $userId = $this->db->lastInsertId();
            
            // Welcome Notification
            $notif = new NotificationService();
            $notif->createNotification($userId, 'user_registered', 'user', $userId, ['name' => $name]);

            Response::success("User created", ['id' => $userId, 'temp_password' => $password]);
        } else {
            Response::error("Failed to create user", 500);
        }
    }

    public function updateUser($id, $data, $admin) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) Response::error("User not found", 404);

        // Security checks
        if (isset($data['role']) && $user['role'] === 'Admin' && $data['role'] !== 'Admin') {
            // Check if this is the last admin
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role = 'Admin' AND status = 'Active'");
            $countStmt->execute();
            if ($countStmt->fetchColumn() <= 1) Response::error("Cannot demote the last active Admin");
        }

        if (isset($data['status']) && $data['status'] === 'Disabled' && $id == $admin['user_id']) {
            Response::error("You cannot disable your own account");
        }

        $fields = [];
        $params = [];
        $allowed = ['name', 'role', 'specialization', 'status', 'is_locked'];

        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) Response::error("No fields to update");

        $query = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $params[] = $id;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        Response::success("User updated");
    }

    public function deleteUser($id, $admin, $hardDelete = false) {
        if ($hardDelete && $admin['role'] !== 'Super Admin') Response::error("Super Admin only", 403);

        if ($id == $admin['user_id']) Response::error("Cannot delete yourself");

        if ($hardDelete) {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        } else {
            $stmt = $this->db->prepare("UPDATE users SET status = 'Deleted' WHERE id = ?");
        }
        
        $stmt->execute([$id]);
        Response::success("User deleted");
    }

    public function getTechnicians($admin) {
        // Only allow authenticated users (admins/managers or any authenticated user to view technicians list)
        $stmt = $this->db->prepare("SELECT id, name, email, specialization FROM users WHERE role = 'Technician' AND status = 'Active' ORDER BY name ASC");
        $stmt->execute();
        $techs = $stmt->fetchAll();

        Response::success("Technicians fetched", ['data' => $techs]);
    }

    public function resetPassword($id, $data, $adminId) {
        $method = $data['method'] ?? 'generate'; // email, generate, manual
        $newPassword = $data['password'] ?? bin2hex(random_bytes(6));

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $id]);

        Response::success("Password reset successful", ['new_password' => $newPassword]);
    }

    public function searchUsers($queryParams) {
        $term = $queryParams['q'] ?? '';
        $role = $queryParams['role'] ?? null;

        $query = "SELECT id, name, email, role FROM users WHERE (name LIKE ? OR email LIKE ?) AND status = 'Active'";
        $params = ["%$term%", "%$term%"];

        if ($role) {
            $query .= " AND role = ?";
            $params[] = $role;
        }

        $stmt = $this->db->prepare($query . " LIMIT 10");
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        Response::success("Search results", $users);
    }
}
