<?php
session_start();
require_once '../config/database.php';
require_once '../config/payments.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Please login first"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$order_id = (int)($data['order_id'] ?? 0);

try {
    $stmt = $conn->prepare("SELECT order_id, user_id, status, payment_status, payment_method, stripe_session_id FROM orders WHERE order_id = :oid");
    $stmt->execute([':oid' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order || (int)$order['user_id'] !== (int)$_SESSION['user_id']) throw new Exception("Order not found");
    if ($order['status'] !== 'Awaiting Payment' || $order['payment_method'] !== 'Stripe' || $order['payment_status'] !== 'Unpaid') {
        throw new Exception("This order is not waiting for online payment");
    }

    $session = stripe_get_session($order['stripe_session_id']);
    if ($session && ($session['status'] ?? '') === 'open' && !empty($session['url'])) {
        echo json_encode(["success" => true, "checkout_url" => $session['url']]);
        exit;
    }

    $result = settle_awaiting_order($conn, $order, 'Online payment time ran out', 'System', true);
    $messages = [
        'paid' => "Your payment for order #$order_id has been received.",
        'refunded' => "Order #$order_id could not be completed and your payment has been refunded.",
        'refund_failed' => "Order #$order_id could not be completed. The store will refund your payment.",
        'cancelled' => "The payment time for order #$order_id has run out, so the order was cancelled. Please order again."
    ];
    echo json_encode(["success" => false, "message" => $messages[$result] ?? "Could not check the payment. Please try again."]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
