<?php
require_once __DIR__ . '/../config/session.php';
require_once '../config/database.php';
require_once '../config/payments.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Please login first"
    ]);
    exit;
}

try {
    expire_stale_payments($conn);

    $stmt = $conn->prepare("
        SELECT
            o.order_id, o.customer_name, o.customer_phone, o.customer_address, o.delivery_type, o.payment_method, o.distance_km, o.slot_date, o.slot_time, o.order_date,
            o.status, o.payment_status, o.total_amount, o.delivery_fee, o.sst_amount, b.branch_name,
            DATE_ADD(o.order_date, INTERVAL " . PAYMENT_WINDOW_MINUTES . " MINUTE) AS pay_before
        FROM orders o
        LEFT JOIN branches b ON o.branch_id = b.branch_id
        WHERE o.user_id = :uid
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt2 = $conn->prepare("
        SELECT oi.product_id, p.name, p.category, p.image_url, oi.quantity, oi.subtotal,
               r.rating AS my_rating, r.comment AS my_comment
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN product_reviews r ON r.order_id = oi.order_id AND r.product_id = oi.product_id
        WHERE oi.order_id = :oid
    ");
    foreach ($orders as &$order) {
        $stmt2->execute([':oid' => $order['order_id']]);
        $order['items'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($order);
    attach_history($conn, $orders);

    echo json_encode([
        "success" => true,
        "data" => $orders
    ]);
} catch(PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => safe_error($e)
    ]);
}
?>
