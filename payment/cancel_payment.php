<?php
require_once '../config/database.php';
require_once '../config/stripe.php';
require_once '../config/order_flow.php';

$order_id = $_GET['order_id'] ?? null;

if ($order_id) {
    $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE order_id = :oid AND payment_status = 'Unpaid' AND payment_method = 'Stripe' AND status = 'Awaiting Payment'");
    $stmt->execute([':oid' => $order_id]);
    if ($stmt->rowCount() > 0) {
        record_status($conn, $order_id, 'Cancelled', 'Online payment was not completed', 'Stripe');
    }
}

header("Location: " . FRONTEND_URL . "/?payment=cancelled");
exit;
?>