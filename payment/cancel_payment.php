<?php
require_once '../config/database.php';
require_once '../config/stripe.php';

$order_id = $_GET['order_id'] ?? null;

if ($order_id) {
    $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE order_id = :oid AND payment_status = 'Unpaid'");
    $stmt->execute([':oid' => $order_id]);
}

header("Location: " . FRONTEND_URL . "/?payment=cancelled");
exit;
?>