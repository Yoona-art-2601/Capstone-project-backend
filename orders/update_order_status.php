<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/audit.php';
require_once '../config/payments.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['order_id']) || !isset($data['status'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields (order_id, status)"]);
    exit;
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("SELECT order_id, status, payment_status, payment_method, delivery_type, branch_id, stock_deducted, stripe_session_id FROM orders WHERE order_id = :oid FOR UPDATE");
    $stmt->execute([':oid' => $data['order_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) throw new Exception("Order not found");
    if ($order['status'] === 'Awaiting Payment') throw new Exception("This order is still awaiting online payment");
    if (in_array($order['status'], ORDER_FINAL_STATUSES, true)) throw new Exception("{$order['status']} orders cannot be changed");

    $new_status = $data['status'];
    if ($new_status === $order['status']) {
        $conn->commit();
        echo json_encode(["success" => true, "message" => "No change"]);
        exit;
    }
    if (!in_array($new_status, allowed_next_statuses($order), true)) {
        throw new Exception("An order that is {$order['status']} cannot be moved to $new_status");
    }

    $notes = [];
    $return_stock = $new_status === 'Cancelled' && (int)$order['stock_deducted'] === 1 && $order['branch_id'] !== null;
    if ($return_stock) {
        return_order_stock($conn, $order);
        $notes[] = 'stock returned to branch';
    }
    $payment_status = $order['payment_status'];
    $refund_failed = false;
    if ($new_status === 'Cancelled' && $order['payment_status'] === 'Paid' && $order['payment_method'] === 'Stripe') {
        $session = null;
        try {
            $session = stripe_get_session($order['stripe_session_id']);
        } catch (Exception $e) {
            error_log("Stripe lookup failed: " . $e->getMessage());
        }
        if ($session && stripe_refund_session($session)) {
            $payment_status = 'Refunded';
            $notes[] = 'online payment refunded';
        } else {
            $refund_failed = true;
            $notes[] = 'automatic refund failed, refund it in the Stripe dashboard';
        }
    }

    if ($new_status === 'Completed' && $order['payment_method'] !== 'Stripe' && $payment_status !== 'Paid') {
        $payment_status = 'Paid';
        $notes[] = $order['payment_method'] === 'COD' ? 'cash collected on delivery' : 'paid at store';
    }

    $conn->prepare("UPDATE orders SET status = :status, payment_status = :pstatus WHERE order_id = :oid")
         ->execute([':status' => $new_status, ':pstatus' => $payment_status, ':oid' => $order['order_id']]);

    $note = $notes ? ucfirst(implode(', ', $notes)) : null;
    record_status($conn, $order['order_id'], $new_status, $note, $_SESSION['name'] ?? 'Admin');
    audit_log($conn, 'ORDER_STATUS', 'order', $order['order_id'],
        "Order #{$order['order_id']}: {$order['status']} -> $new_status" . ($note ? " ($note)" : ''));

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => $refund_failed
            ? "Order cancelled, but the automatic refund failed. Please refund it in the Stripe dashboard"
            : ($payment_status === 'Refunded' && $order['payment_status'] !== 'Refunded' ? "Order cancelled and the online payment refunded" : "Order status updated successfully"),
        "payment_status" => $payment_status
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
