<?php
require_once '../config/database.php';
require_once '../config/payments.php';

$session_id = $_GET['session_id'] ?? '';

function back_to_orders($params) {
    header("Location: " . FRONTEND_URL . "/my-orders?" . http_build_query($params));
    exit;
}

try {
    $session = stripe_get_session($session_id);
    if (!$session) back_to_orders(['payment' => 'error']);

    $stmt = $conn->prepare("SELECT order_id FROM orders WHERE stripe_session_id = :sid");
    $stmt->execute([':sid' => $session['id']]);
    $order_id = $stmt->fetchColumn();
    if (!$order_id) back_to_orders(['payment' => 'error']);

    if (($session['payment_status'] ?? '') !== 'paid') {
        back_to_orders(['payment' => 'pending', 'order' => $order_id]);
    }

    $result = finalize_stripe_order($conn, $order_id, $session);
    if ($result === 'paid') back_to_orders(['placed' => $order_id]);
    back_to_orders(['refunded' => $order_id]);

} catch (Exception $e) {
    error_log("confirm_payment failed: " . $e->getMessage());
    back_to_orders(['payment' => 'error']);
}
?>
