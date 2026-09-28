<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

try {
    // get all orders record from the orders table, ordered by order_date descending
    $stmt = $conn->prepare("SELECT * FROM orders ORDER BY order_date DESC");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // compare each order with its items
    foreach ($orders as &$order) {
        $stmt2 = $conn->prepare("
            SELECT oi.product_id, p.name, oi.quantity, oi.subtotal
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = :oid
        ");
        $stmt2->execute([':oid' => $order['order_id']]);
        $order['items'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }

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