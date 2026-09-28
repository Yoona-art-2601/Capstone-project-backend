<?php
require_once '../config/database.php';

try {
    $stmt = $conn->prepare("
        SELECT 
            sl.log_id,
            sl.product_id,
            p.name,
            sl.change_type,
            sl.quantity_change,
            sl.log_date
        FROM stock_logs sl
        JOIN products p ON sl.product_id = p.product_id
        ORDER BY sl.log_date DESC
    ");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $logs
    ]);
} catch(PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>