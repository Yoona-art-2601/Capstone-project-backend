<?php
require_once '../config/database.php';
require_once '../auth/check_staff.php';

$per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$branch_id = staff_branch() ?? (int)($_GET['branch_id'] ?? 0);
$status = $_GET['status'] ?? 'In Transit';

$where = [];
$params = [];
if ($branch_id) {
    $where[] = "d.branch_id = :bid";
    $params[':bid'] = $branch_id;
}
if (in_array($status, ['In Transit', 'Received', 'Cancelled'], true)) {
    $where[] = "d.status = :status";
    $params[':status'] = $status;
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM warehouse_dispatches d $where_sql");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $stmt = $conn->prepare("
        SELECT d.dispatch_id, d.product_id, p.name AS product_name, p.category, d.branch_id, b.branch_name, d.quantity, d.status,
               d.dispatched_at, d.expected_arrival, d.received_at, d.dispatched_by, d.received_by, d.note,
               d.status = 'In Transit' AND d.expected_arrival < NOW() AS overdue
        FROM warehouse_dispatches d
        JOIN products p ON p.product_id = d.product_id
        JOIN branches b ON b.branch_id = d.branch_id
        $where_sql
        ORDER BY FIELD(d.status, 'In Transit', 'Received', 'Cancelled'), d.expected_arrival, d.dispatch_id DESC
        LIMIT $per_page OFFSET " . (($page - 1) * $per_page)
    );
    $stmt->execute($params);

    $counts_sql = "SELECT d.status, COUNT(*) FROM warehouse_dispatches d" . ($branch_id ? " WHERE d.branch_id = :bid" : "") . " GROUP BY d.status";
    $counts = $conn->prepare($counts_sql);
    $counts->execute($branch_id ? [':bid' => $branch_id] : []);

    echo json_encode([
        "success" => true,
        "data" => $stmt->fetchAll(PDO::FETCH_ASSOC),
        "page" => $page,
        "pages" => max(1, (int)ceil($total / $per_page)),
        "total" => $total,
        "counts" => (object)$counts->fetchAll(PDO::FETCH_KEY_PAIR),
        "can_cancel" => staff_branch() === null
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
