<?php

namespace App\Controllers;

use App\Services\AnalyticsService;
use App\Utils\Response;
use App\Config\Database;
use PDO;

class ReportController {
    private $analytics;
    private $db;

    public function __construct() {
        $this->analytics = new AnalyticsService();
        $this->db = Database::getInstance()->getConnection();
    }

    public function getTimeline($queryParams, $user) {
        if ($user['role'] !== 'Admin') Response::error("Forbidden", 403);

        $start = $queryParams['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $end = $queryParams['end_date'] ?? date('Y-m-d');
        $interval = $queryParams['interval'] ?? 'day';

        $data = $this->analytics->getRequestsTimeline($start, $end, $interval);
        Response::success("Timeline fetched", $data);
    }

    public function getCategoryStats($queryParams, $user) {
        if ($user['role'] !== 'Admin') Response::error("Forbidden", 403);

        $start = $queryParams['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $end = $queryParams['end_date'] ?? date('Y-m-d');

        $data = $this->analytics->getCategoryDistribution($start, $end);
        Response::success("Category stats fetched", $data);
    }

    public function getTechPerformance($user) {
        if ($user['role'] !== 'Admin') Response::error("Forbidden", 403);

        $data = $this->analytics->getTechnicianPerformance();
        Response::success("Technician performance fetched", $data);
    }

    public function getAuditLogs($queryParams, $user) {
        if ($user['role'] !== 'Admin' && $user['role'] !== 'Super Admin') Response::error("Forbidden", 403);
        $data = $this->analytics->getAuditLogs($queryParams);
        Response::success("Audit logs fetched", $data);
    }

    public function exportCSV($queryParams, $user) {
        if ($user['role'] !== 'Admin') Response::error("Forbidden", 403);

        $type = $queryParams['type'] ?? 'requests'; // requests, inventory, tasks
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $type . '_export_' . date('Ymd') . '.csv"');

        $output = fopen('php://output', 'w');

        if ($type === 'requests') {
            fputcsv($output, ['ID', 'Title', 'Status', 'Priority', 'Category', 'Submitted By', 'Created At']);
            $stmt = $this->db->prepare("SELECT r.id, r.title, r.status, r.priority, r.category, u.name, r.created_at FROM maintenance_requests r JOIN users u ON r.user_id = u.id");
            $stmt->execute();
        } elseif ($type === 'inventory') {
            fputcsv($output, ['ID', 'Item Name', 'Category', 'Quantity', 'Unit', 'Reorder Level']);
            $stmt = $this->db->prepare("SELECT id, item_name, category, quantity, unit, reorder_level FROM inventory WHERE is_active = TRUE");
            $stmt->execute();
        }

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}
