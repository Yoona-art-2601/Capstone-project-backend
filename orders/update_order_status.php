<?php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['order_id']) || !isset($data['status'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields (order_id, status)"
    ]);
    exit;
}

// limit the allowed status values to prevent invalid updates
$allowed_status = ['Pending', 'Processing', 'Completed', 'Cancelled'];
if (!in_array($data['status'], $allowed_status)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid status. Allowed: Pending, Processing, Completed, Cancelled"
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE orders SET status = :status WHERE order_id = :oid");
    $stmt->execute([
        ':status' => $data['status'],
        ':oid' => $data['order_id']
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Order status updated successfully"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Order not found or status unchanged"
        ]);
    }
} catch(PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>