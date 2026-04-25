<?php

namespace App\Controllers;

use App\Config\Database;
use App\Utils\Response;
use App\Utils\JWTHandler;
use PDO;

class AuthController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function register($data) {
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'User';

        // Validation
        if (!$name || !$email || !$password) {
            Response::error("Missing required fields");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error("Invalid email format");
        }

        if (strlen($password) < 8) {
            Response::error("Password must be at least 8 characters long");
        }

        // Check if email exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            Response::error("Email already exists");
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

        // Create user
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'Active')");
        if ($stmt->execute([$name, $email, $passwordHash, $role])) {
            Response::success("User registered successfully");
        } else {
            Response::error("Failed to register user", 500);
        }
    }

    public function login($data) {
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $selectedRole = $data['role'] ?? null;

        if (!$email || !$password) {
            Response::error("Email and password are required");
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Response::error("Invalid credentials", 401);
        }

        if ($user['status'] !== 'Active') {
            Response::error("Account is disabled", 403);
        }

        if ($selectedRole && $user['role'] !== $selectedRole) {
            Response::error("Role mismatch", 403);
        }

        // Generate Token
        $token = JWTHandler::encode([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ]);

        Response::success("Login successful", [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    }
}
