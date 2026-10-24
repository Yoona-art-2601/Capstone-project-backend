<?php
session_start();
require_once '../config/database.php';
require_once '../config/audit.php';
require_once '../config/payments.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Please login first"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$order_id = (int)($data['order_id'] ?? 0);

try {
    $stmt = $conn->prepare("SELECT order_id, user_id, status, payment_status, payment_method, stripe_session_id FROM orders WHERE order_id = :oid");
    $stmt->execute([':oid' => $order_id]);
    $online = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($online && (int)$online['user_id'] === (int)$_SESSION['user_id']
        && $online['status'] === 'Awaiting Payment' && $online['payment_method'] === 'Stripe' && $online['payment_status'] === 'Unpaid') {
        $result = settle_awaiting_order($conn, $online, 'Cancelled by customer', $_SESSION['name'] ?? 'Customer', true);
        if ($result === 'cancelled') {
            audit_log($conn, 'ORDER_CANCEL', 'order', $order_id, "Order #$order_id cancelled by customer");
            echo json_encode(["success" => true, "message" => "Order cancelled"]);
        } elseif ($result === 'paid') {
            echo json_encode(["success" => false, "message" => "Your payment for this order has just been received, so it can no longer be cancelled online. Please contact the store"]);
        } elseif ($result === 'unknown') {
            echo json_encode(["success" => false, "message" => "Could not reach the payment provider. Please try again"]);
        } else {
            echo json_encode(["success" => true, "message" => "Order cancelled and payment refunded"]);
        }
        exit;
    }

    $conn->beginTransaction();

    $stmt = $conn->prepare("SELECT order_id, user_id, status, payment_status, payment_method, branch_id, stock_deducted FROM orders WHERE order_id = :oid FOR UPDATE");
    $stmt->execute([':oid' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order || (int)$order['user_id'] !== (int)$_SESSION['user_id']) throw new Exception("Order not found");
    if (!in_array($order['status'], ['Awaiting Payment', 'Pending'], true)) {
        throw new Exception("This order is already being prepared and can no longer be cancelled online. Please contact the store");
    }
    if ($order['payment_status'] === 'Paid') {
        throw new Exception("This order has been paid online. Please contact the store to cancel it and get a refund");
    }

    if ((int)$order['stock_deducted'] === 1 && $order['branch_id'] !== null) {
        return_order_stock($conn, $order);
    }

    $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE order_id = :oid")->execute([':oid' => $order_id]);
    record_status($conn, $order_id, 'Cancelled', 'Cancelled by customer', $_SESSION['name'] ?? 'Customer');
    notify_admin_order($conn, $order_id, 'customer_cancelled');
    audit_log($conn, 'ORDER_CANCEL', 'order', $order_id, "Order #$order_id cancelled by customer");

    $conn->commit();
    echo json_encode(["success" => true, "message" => "Order cancelled"]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
