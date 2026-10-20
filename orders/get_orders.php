<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/order_flow.php';

$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : null;
$branch_filter = $branch_id ? "WHERE o.branch_id = :branch_id" : "";

try {
    $stmt = $conn->prepare("
        SELECT
            o.order_id, o.customer_name, o.customer_phone, o.customer_address, o.delivery_type, o.payment_method, o.distance_km, o.slot_date, o.slot_time, o.order_date,
            o.status, o.payment_status, o.total_amount, o.delivery_fee, o.sst_amount,
            o.branch_id, b.branch_name
        FROM orders o
        LEFT JOIN branches b ON o.branch_id = b.branch_id
        $branch_filter
        ORDER BY o.order_date DESC
    ");
    if ($branch_id) $stmt->bindValue(':branch_id', $branch_id);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    unset($order);
    attach_history($conn, $orders);

    echo json_encode([
        "success" => true,
        "branch_id" => $branch_id ?? "all",
        "data" => $orders
    ]);
} catch(PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>