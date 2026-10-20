<?php
require_once __DIR__ . '/notifications.php';
define('ORDER_FINAL_STATUSES', ['Completed', 'Cancelled']);

function order_steps($delivery_type) {
    return ['Pending', 'Processing', $delivery_type === 'Pickup' ? 'Ready for Pickup' : 'Out for Delivery', 'Completed'];
}

function allowed_next_statuses($order) {
    if (in_array($order['status'], ORDER_FINAL_STATUSES, true) || $order['status'] === 'Awaiting Payment') return [];
    $steps = order_steps($order['delivery_type']);
    $index = array_search($order['status'], $steps, true);
    $next = $index === false ? [] : array_slice($steps, $index + 1);
    $next[] = 'Cancelled';
    return $next;
}

function record_status($conn, $order_id, $status, $note = null, $changed_by = null) {
    $conn->prepare("INSERT INTO order_status_history (order_id, status, note, changed_by) VALUES (:oid, :status, :note, :by)")
         ->execute([':oid' => $order_id, ':status' => $status, ':note' => $note, ':by' => $changed_by]);

    $stmt = $conn->prepare("SELECT o.user_id, o.delivery_type, b.branch_name FROM orders o LEFT JOIN branches b ON b.branch_id = o.branch_id WHERE o.order_id = :oid");
    $stmt->execute([':oid' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order || !$order['user_id'] || $status === 'Awaiting Payment') return;

    $pickup = $order['delivery_type'] === 'Pickup';
    $messages = [
        'Pending' => ["Order #$order_id received", $note === 'Online payment received'
            ? "We have received your payment. {$order['branch_name']} will start preparing your order soon."
            : "Your order has been placed with {$order['branch_name']}."],
        'Processing' => ["Order #$order_id is being prepared", "{$order['branch_name']} is packing your items."],
        'Out for Delivery' => ["Order #$order_id is on the way", "Your order has left {$order['branch_name']} and will arrive in your chosen time slot."],
        'Ready for Pickup' => ["Order #$order_id is ready for pickup", "Please collect it at {$order['branch_name']}."],
        'Completed' => [$pickup ? "Order #$order_id picked up" : "Order #$order_id delivered", "Thank you for shopping with Beacon Mart. You can now rate your items."],
        'Cancelled' => ["Order #$order_id was cancelled", $note ? "$note." : "Your order has been cancelled."]
    ];
    if (!isset($messages[$status])) return;
    notify($conn, 'Customer', $order['user_id'], 'order_status', $messages[$status][0], $messages[$status][1], '/my-orders', "order:$order_id");
}

function return_order_stock($conn, $order) {
    $stmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = :oid");
    $stmt->execute([':oid' => $order['order_id']]);
    $upd = $conn->prepare("UPDATE branch_stock SET stock = stock + :qty WHERE product_id = :pid AND branch_id = :bid");
    $log = $conn->prepare("INSERT INTO stock_logs (product_id, branch_id, change_type, quantity_change) VALUES (:pid, :bid, 'Cancel Return', :qty)");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $upd->execute([':qty' => $item['quantity'], ':pid' => $item['product_id'], ':bid' => $order['branch_id']]);
        $log->execute([':pid' => $item['product_id'], ':bid' => $order['branch_id'], ':qty' => $item['quantity']]);
    }
    $conn->prepare("UPDATE orders SET stock_deducted = 0 WHERE order_id = :oid")->execute([':oid' => $order['order_id']]);
    check_stock_levels($conn, $order['branch_id'], order_product_ids($conn, $order['order_id']));
}

function attach_history($conn, &$orders) {
    $stmt = $conn->prepare("SELECT status, note, changed_by, changed_at FROM order_status_history WHERE order_id = :oid ORDER BY changed_at, history_id");
    foreach ($orders as &$order) {
        $stmt->execute([':oid' => $order['order_id']]);
        $order['history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $order['steps'] = order_steps($order['delivery_type'] ?? 'Delivery');
    }
    unset($order);
}
?>
