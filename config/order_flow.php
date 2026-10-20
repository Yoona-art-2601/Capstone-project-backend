<?php
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
