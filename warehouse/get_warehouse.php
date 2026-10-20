<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/warehouse.php';

$per_page = 10;
$receipt_page = max(1, (int)($_GET['receipt_page'] ?? 1));
$dispatch_page = max(1, (int)($_GET['dispatch_page'] ?? 1));
$status = $_GET['status'] ?? '';

try {
    $warehouse = $conn->query("SELECT warehouse_id, name, address FROM warehouses WHERE warehouse_id = " . MAIN_WAREHOUSE_ID)->fetch(PDO::FETCH_ASSOC);

    $stock = $conn->query("
        SELECT p.product_id, p.name, p.category, COALESCE(ws.stock, 0) AS stock,
               COALESCE((SELECT SUM(d.quantity) FROM warehouse_dispatches d WHERE d.product_id = p.product_id AND d.status = 'In Transit'), 0) AS in_transit
        FROM products p
        LEFT JOIN warehouse_stock ws ON ws.product_id = p.product_id AND ws.warehouse_id = " . MAIN_WAREHOUSE_ID . "
        WHERE p.is_active = 1
        ORDER BY p.category, p.name
    ")->fetchAll(PDO::FETCH_ASSOC);

    $receipt_total = (int)$conn->query("SELECT COUNT(*) FROM factory_receipts")->fetchColumn();
    $offset = ($receipt_page - 1) * $per_page;
    $receipts = $conn->query("
        SELECT r.receipt_id, p.name AS product_name, r.quantity, r.supplier, r.reference, r.received_at, r.received_by
        FROM factory_receipts r JOIN products p ON p.product_id = r.product_id
        ORDER BY r.received_at DESC, r.receipt_id DESC
        LIMIT $per_page OFFSET $offset
    ")->fetchAll(PDO::FETCH_ASSOC);

    $where = in_array($status, ['In Transit', 'Received', 'Cancelled'], true) ? "WHERE d.status = :status" : "";
    $params = $where ? [':status' => $status] : [];
    $stmt = $conn->prepare("SELECT COUNT(*) FROM warehouse_dispatches d $where");
    $stmt->execute($params);
    $dispatch_total = (int)$stmt->fetchColumn();
    $offset = ($dispatch_page - 1) * $per_page;
    $stmt = $conn->prepare("
        SELECT d.dispatch_id, p.name AS product_name, b.branch_name, d.quantity, d.status, d.dispatched_at,
               d.expected_arrival, d.received_at, d.dispatched_by, d.received_by, d.note
        FROM warehouse_dispatches d
        JOIN products p ON p.product_id = d.product_id
        JOIN branches b ON b.branch_id = d.branch_id
        $where
        ORDER BY FIELD(d.status, 'In Transit', 'Received', 'Cancelled'), d.dispatched_at DESC, d.dispatch_id DESC
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $dispatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "warehouse" => $warehouse,
        "stock" => $stock,
        "receipts" => ["data" => $receipts, "page" => $receipt_page, "pages" => max(1, (int)ceil($receipt_total / $per_page)), "total" => $receipt_total],
        "dispatches" => ["data" => $dispatches, "page" => $dispatch_page, "pages" => max(1, (int)ceil($dispatch_total / $per_page)), "total" => $dispatch_total]
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
