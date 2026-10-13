<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Please login first"
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT
            o.order_id, o.customer_name, o.customer_phone, o.customer_address, o.order_date,
            o.status, o.payment_status, o.total_amount, b.branch_name
        FROM orders o
        LEFT JOIN branches b ON o.branch_id = b.branch_id
        WHERE o.user_id = :uid
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt2 = $conn->prepare("
        SELECT p.name, oi.quantity, oi.subtotal
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = :oid
    ");
    foreach ($orders as &$order) {
        $stmt2->execute([':oid' => $order['order_id']]);
        $order['items'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($order);

    echo json_encode([
        "success" => true,
        "data" => $orders
    ]);
} catch(PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
