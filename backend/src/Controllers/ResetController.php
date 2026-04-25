<?php

namespace App\Controllers;

use App\Config\Database;
use App\Utils\Response;
use PDO;
use Exception;

class ResetController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function requestReset($data) {
        $email = $data['email'] ?? '';

        if (!$email) {
            Response::error("Email is required");
        }

        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // For security, don't reveal if user exists
            Response::success("If that email exists, a reset link has been sent.");
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $this->db->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $token, $expires]);

        // Mock sending email
        // mail($email, "Password Reset", "Your token: " . $token);

        Response::success("If that email exists, a reset link has been sent.", ['token' => $token]); // Returning token for demo
    }

    public function resetPassword($data) {
        $token = $data['token'] ?? '';
        $newPassword = $data['password'] ?? '';

        if (!$token || !$newPassword) {
            Response::error("Token and new password are required");
        }

        if (strlen($newPassword) < 8) {
            Response::error("Password must be at least 8 characters long");
        }

        $stmt = $this->db->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();

        if (!$reset) {
            Response::error("Invalid or expired token");
        }

        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);

        $this->db->beginTransaction();
        try {
            // Update password
            $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$passwordHash, $reset['user_id']]);

            // Delete used token
            $stmt = $this->db->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);

            $this->db->commit();
            Response::success("Password updated successfully");
        } catch (Exception $e) {
            $this->db->rollBack();
            Response::error("Failed to update password", 500);
        }
    }
}
