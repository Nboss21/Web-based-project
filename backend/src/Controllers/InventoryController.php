<?php

namespace App\Controllers;

use App\Config\Database;
use App\Utils\Response;
use PDO;
use Exception;

class InventoryController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function listItems($queryParams, $user) {
        if ($user['role'] !== 'Admin' && $user['role'] !== 'Manager' && $user['role'] !== 'Store Manager') {
            Response::error("Forbidden", 403);
        }

        $page = (int)($queryParams['page'] ?? 1);
        $limit = (int)($queryParams['limit'] ?? 10);
        $offset = ($page - 1) * $limit;

        $conditions = ["is_active = TRUE"];
        $params = [];

        if (!empty($queryParams['category'])) {
            $conditions[] = "category = ?";
            $params[] = $queryParams['category'];
        }

        if (!empty($queryParams['search'])) {
            $conditions[] = "item_name LIKE ?";
            $params[] = "%" . $queryParams['search'] . "%";
        }

        if (!empty($queryParams['low_stock_only'])) {
            $conditions[] = "quantity <= reorder_level";
        }

        $whereClause = "WHERE " . implode(" AND ", $conditions);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM inventory $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT *, 
            CASE 
                WHEN quantity = 0 THEN 'Out'
                WHEN quantity <= reorder_level * 0.5 THEN 'Critical'
                WHEN quantity <= reorder_level THEN 'Low'
                ELSE 'Healthy'
            END as stock_status
            FROM inventory 
            $whereClause 
            ORDER BY item_name ASC 
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        Response::success("Inventory fetched", [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'data' => $items
        ]);
    }

    public function createItem($data, $userId) {
        $name = $data['name'] ?? '';
        $category = $data['category'] ?? 'General';
        $quantity = (int)($data['quantity'] ?? 0);
        $unit = $data['unit'] ?? 'unit';
        $reorderLevel = (int)($data['reorder_level'] ?? 5);

        if (!$name) Response::error("Item name is required");

        $stmt = $this->db->prepare("INSERT INTO inventory (item_name, category, quantity, unit, reorder_level, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $category, $quantity, $unit, $reorderLevel, $userId])) {
            $itemId = $this->db->lastInsertId();
            $this->checkLowStock($itemId, $quantity, $reorderLevel);
            Response::success("Item created", ['id' => $itemId]);
        } else {
            Response::error("Failed to create item", 500);
        }
    }

    public function updateItem($id, $data, $userId) {
        $stmt = $this->db->prepare("SELECT * FROM inventory WHERE id = ? AND is_active = TRUE");
        $stmt->execute([$id]);
        $item = $stmt->fetch();

        if (!$item) Response::error("Item not found", 404);

        $name = $data['name'] ?? $item['item_name'];
        $category = $data['category'] ?? $item['category'];
        $reorderLevel = (int)($data['reorder_level'] ?? $item['reorder_level']);
        $unit = $data['unit'] ?? $item['unit'];

        $stmt = $this->db->prepare("UPDATE inventory SET item_name = ?, category = ?, reorder_level = ?, unit = ? WHERE id = ?");
        $stmt->execute([$name, $category, $reorderLevel, $unit, $id]);

        Response::success("Item updated");
    }

    public function adjustStock($id, $data, $userId) {
        $type = $data['adjustment_type'] ?? null; // Add, Remove, Set
        $quantity = (int)($data['quantity'] ?? 0);
        $reason = $data['reason'] ?? '';

        if (!$type || $quantity < 0) Response::error("Invalid adjustment parameters");

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM inventory WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $item = $stmt->fetch();

            if (!$item) throw new Exception("Item not found");

            $before = $item['quantity'];
            $after = $before;

            if ($type === 'Add') $after += $quantity;
            elseif ($type === 'Remove') {
                if ($before < $quantity) throw new Exception("Insufficient stock");
                $after -= $quantity;
            } elseif ($type === 'Set') $after = $quantity;

            $stmt = $this->db->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
            $stmt->execute([$after, $id]);

            // Transaction log
            $stmt = $this->db->prepare("INSERT INTO inventory_transactions (item_id, user_id, type, quantity_change, before_quantity, after_quantity, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $userId, $type, ($type === 'Remove' ? -$quantity : $quantity), $before, $after, $reason]);

            $this->checkLowStock($id, $after, $item['reorder_level']);

            $this->db->commit();
            Response::success("Stock adjusted", ['new_quantity' => $after]);

        } catch (Exception $e) {
            $this->db->rollBack();
            Response::error($e->getMessage());
        }
    }

    public function getPendingRequests() {
        $stmt = $this->db->prepare("
            SELECT r.*, t.request_id as maintenance_request_id, u.name as technician_name
            FROM material_requests r
            JOIN tasks t ON r.task_id = t.id
            JOIN users u ON r.requested_by = u.id
            WHERE r.status = 'Pending'
        ");
        $stmt->execute();
        $requests = $stmt->fetchAll();

        foreach ($requests as &$req) {
            $stmt = $this->db->prepare("
                SELECT i.item_name, ri.quantity, i.quantity as stock_available
                FROM material_request_items ri
                JOIN inventory i ON ri.item_id = i.id
                WHERE ri.request_id = ?
            ");
            $stmt->execute([$req['id']]);
            $req['items'] = $stmt->fetchAll();
        }

        Response::success("Pending requests fetched", ['data' => $requests]);
    }

    public function processRequest($id, $action, $data, $userId) {
        $stmt = $this->db->prepare("SELECT * FROM material_requests WHERE id = ? AND status = 'Pending'");
        $stmt->execute([$id]);
        $request = $stmt->fetch();

        if (!$request) Response::error("Request not found", 404);

        if ($action === 'approve') {
            $this->db->beginTransaction();
            try {
                // Fetch items
                $stmt = $this->db->prepare("SELECT * FROM material_request_items WHERE request_id = ?");
                $stmt->execute([$id]);
                $items = $stmt->fetchAll();

                foreach ($items as $item) {
                    $stmt = $this->db->prepare("SELECT * FROM inventory WHERE id = ? FOR UPDATE");
                    $stmt->execute([$item['item_id']]);
                    $inv = $stmt->fetch();

                    if ($inv['quantity'] < $item['quantity']) {
                        throw new Exception("Insufficient stock for item: " . $inv['item_name']);
                    }

                    $newQty = $inv['quantity'] - $item['quantity'];
                    $stmt = $this->db->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
                    $stmt->execute([$newQty, $item['item_id']]);

                    // Transaction
                    $stmt = $this->db->prepare("INSERT INTO inventory_transactions (item_id, user_id, type, quantity_change, before_quantity, after_quantity, reason) VALUES (?, ?, 'Deduction', ?, ?, ?, ?)");
                    $stmt->execute([$item['item_id'], $userId, -$item['quantity'], $inv['quantity'], $newQty, "Material Request Approval #$id"]);

                    $this->checkLowStock($item['item_id'], $newQty, $inv['reorder_level']);
                }

                $stmt = $this->db->prepare("UPDATE material_requests SET status = 'Approved', processed_by = ? WHERE id = ?");
                $stmt->execute([$userId, $id]);

                $this->db->commit();
                Response::success("Request approved and inventory updated");

            } catch (Exception $e) {
                $this->db->rollBack();
                Response::error($e->getMessage());
            }
        } else {
            $reason = $data['reason'] ?? 'Rejected by manager';
            $stmt = $this->db->prepare("UPDATE material_requests SET status = 'Rejected', rejection_reason = ?, processed_by = ? WHERE id = ?");
            $stmt->execute([$reason, $userId, $id]);
            Response::success("Request rejected");
        }
    }

    private function checkLowStock($itemId, $current, $threshold) {
        if ($current <= $threshold) {
            $stmt = $this->db->prepare("SELECT id FROM low_stock_alerts WHERE item_id = ? AND is_resolved = FALSE");
            $stmt->execute([$itemId]);
            if (!$stmt->fetch()) {
                $stmt = $this->db->prepare("INSERT INTO low_stock_alerts (item_id) VALUES (?)");
                $stmt->execute([$itemId]);
                
                // Notify Store Manager
                $stmtName = $this->db->prepare("SELECT item_name FROM inventory WHERE id = ?");
                $stmtName->execute([$itemId]);
                $itemName = $stmtName->fetchColumn();

                $notifService = new \App\Services\NotificationService();
                $notifService->notifyStoreManagers('low_stock', 'inventory', $itemId, ['name' => $itemName]);
            }
        } else {
            $stmt = $this->db->prepare("UPDATE low_stock_alerts SET is_resolved = TRUE, resolved_at = NOW() WHERE item_id = ? AND is_resolved = FALSE");
            $stmt->execute([$itemId]);
        }
    }
}
