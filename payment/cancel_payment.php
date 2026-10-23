<?php
session_start();
require_once '../config/database.php';
require_once '../config/payments.php';

$order_id = (int)($_GET['order_id'] ?? 0);

try {
    $stmt = $conn->prepare("SELECT order_id, user_id, status, payment_status, payment_method, stripe_session_id FROM orders WHERE order_id = :oid");
    $stmt->execute([':oid' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    $is_owner = $order && isset($_SESSION['user_id']) && (int)$order['user_id'] === (int)$_SESSION['user_id'];
    if ($is_owner && $order['status'] === 'Awaiting Payment' && $order['payment_method'] === 'Stripe' && $order['payment_status'] === 'Unpaid') {
        $result = settle_awaiting_order($conn, $order, 'Online payment was not completed', $_SESSION['name'] ?? 'Customer', true);
        if ($result === 'paid') {
            header("Location: " . FRONTEND_URL . "/my-orders?placed=" . $order_id);
            exit;
        }
        if ($result === 'refunded' || $result === 'refund_failed') {
            header("Location: " . FRONTEND_URL . "/my-orders?refunded=" . $order_id);
            exit;
        }
    }
} catch (Exception $e) {
    error_log("cancel_payment failed: " . $e->getMessage());
}

header("Location: " . FRONTEND_URL . "/?payment=cancelled");
exit;
?>
