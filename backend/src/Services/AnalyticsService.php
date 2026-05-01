<?php

namespace App\Services;

use App\Config\Database;
use PDO;
use Exception;

class AnalyticsService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get or Set Cache
     */
    private function getCache($key) {
        $stmt = $this->db->prepare("SELECT cache_value FROM analytics_cache WHERE cache_key = ? AND expires_at > NOW()");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val ? json_decode($val, true) : null;
    }

    private function setCache($key, $value, $ttlSeconds = 300) {
        $expires = date('Y-m-d H:i:s', time() + $ttlSeconds);
        $stmt = $this->db->prepare("
            INSERT INTO analytics_cache (cache_key, cache_value, expires_at) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE cache_value = VALUES(cache_value), expires_at = VALUES(expires_at)
        ");
        $stmt->execute([$key, json_encode($value), $expires]);
    }

    /**
     * Timeline of requests
     */
    public function getRequestsTimeline($startDate, $endDate, $interval = 'day') {
        $cacheKey = "timeline_{$startDate}_{$endDate}_{$interval}";
        if ($cached = $this->getCache($cacheKey)) return $cached;

        $format = "%Y-%m-%d";
        if ($interval === 'week') $format = "%x-%v"; // Year and week
        elseif ($interval === 'month') $format = "%Y-%m";

        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(created_at, ?) as date,
                COUNT(*) as submitted,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
            FROM maintenance_requests
            WHERE created_at BETWEEN ? AND ?
            GROUP BY date
            ORDER BY date ASC
        ");
        $stmt->execute([$format, $startDate . " 00:00:00", $endDate . " 23:59:59"]);
        $result = $stmt->fetchAll();

        $this->setCache($cacheKey, $result);
        return $result;
    }

    /**
     * Distribution by category
     */
    public function getCategoryDistribution($startDate, $endDate) {
        $cacheKey = "cat_dist_{$startDate}_{$endDate}";
        if ($cached = $this->getCache($cacheKey)) return $cached;

        $stmt = $this->db->prepare("
            SELECT 
                category,
                COUNT(*) as count,
                (COUNT(*) * 100 / (SELECT COUNT(*) FROM maintenance_requests WHERE created_at BETWEEN ? AND ?)) as percentage
            FROM maintenance_requests
            WHERE created_at BETWEEN ? AND ?
            GROUP BY category
        ");
        $stmt->execute([$startDate . " 00:00:00", $endDate . " 23:59:59", $startDate . " 00:00:00", $endDate . " 23:59:59"]);
        $result = $stmt->fetchAll();

        $this->setCache($cacheKey, $result);
        return $result;
    }

    /**
     * Technician Performance
     */
    public function getTechnicianPerformance() {
        $cacheKey = "tech_perf";
        if ($cached = $this->getCache($cacheKey)) return $cached;

        $stmt = $this->db->prepare("
            SELECT 
                u.id, u.name,
                COUNT(t.id) as total_assigned,
                SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as total_completed,
                (SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) / COUNT(t.id)) * 100 as completion_rate,
                AVG(TIMESTAMPDIFF(SECOND, t.start_time, t.completion_time)) / 3600 as avg_resolution_hours
            FROM users u
            JOIN tasks t ON u.id = t.assigned_to
            WHERE u.role = 'Technician'
            GROUP BY u.id
            ORDER BY completion_rate DESC
        ");
        $stmt->execute();
        $result = $stmt->fetchAll();

        $this->setCache($cacheKey, $result);
        return $result;
    }

    public function getAuditLogs($queryParams) {
        $limit = (int)($queryParams['limit'] ?? 50);
        $offset = (int)($queryParams['offset'] ?? 0);

        $stmt = $this->db->prepare("
            SELECT a.*, u.name as user_name 
            FROM audit_logs a 
            LEFT JOIN users u ON a.user_id = u.id 
            ORDER BY a.created_at DESC 
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
