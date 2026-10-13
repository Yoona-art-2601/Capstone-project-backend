<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';

$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : null;
$branch_filter = $branch_id ? "AND sl.branch_id = :branch_id" : "";

try {
    $stmt = $conn->prepare("
        SELECT 
            sl.log_id,
            sl.product_id,
            p.name,
            sl.branch_id,
            b.branch_name,
            sl.change_type,
            sl.quantity_change,
            sl.log_date
        FROM stock_logs sl
        JOIN products p ON sl.product_id = p.product_id
        LEFT JOIN branches b ON sl.branch_id = b.branch_id
        WHERE 1=1
        $branch_filter
        ORDER BY sl.log_date DESC
    ");
    if ($branch_id) $stmt->bindValue(':branch_id', $branch_id);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "branch_id" => $branch_id ?? "all",
        "data" => $logs
    ]);
} catch(PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>