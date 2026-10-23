<?php
require_once __DIR__ . '/stripe.php';
require_once __DIR__ . '/order_flow.php';
require_once __DIR__ . '/shop.php';

function stripe_get_session($session_id) {
    if (!$session_id) return null;
    $session = stripe_request('GET', 'checkout/sessions/' . urlencode($session_id));
    return isset($session['id']) ? $session : null;
}

function stripe_expire_session($session_id) {
    if (!$session_id) return;
    try {
        stripe_request('POST', 'checkout/sessions/' . urlencode($session_id) . '/expire');
    } catch (Exception $e) {
        error_log("Stripe expire failed: " . $e->getMessage());
    }
}

function stripe_refund_session($session) {
    $intent = $session['payment_intent'] ?? null;
    if (is_array($intent)) $intent = $intent['id'] ?? null;
    if (!$intent) return false;
    try {
        $refund = stripe_request('POST', 'refunds', ['payment_intent' => $intent]);
        return isset($refund['id']) && in_array($refund['status'] ?? '', ['succeeded', 'pending'], true);
    } catch (Exception $e) {
        error_log("Stripe refund failed: " . $e->getMessage());
        return false;
    }
}

function refund_and_cancel($conn, $order, $session, $reason) {
    $refunded = stripe_refund_session($session);
    $conn->prepare("UPDATE orders SET status = 'Cancelled', payment_status = :ps WHERE order_id = :oid")
         ->execute([':ps' => $refunded ? 'Refunded' : 'Paid', ':oid' => $order['order_id']]);
    $note = $reason . ($refunded ? '. Your payment has been refunded' : '. The store will refund your payment');
    if ($order['status'] !== 'Cancelled') {
        record_status($conn, $order['order_id'], 'Cancelled', $note, 'System');
    } else {
        $conn->prepare("INSERT INTO order_status_history (order_id, status, note, changed_by) VALUES (:oid, 'Cancelled', :note, 'System')")
             ->execute([':oid' => $order['order_id'], ':note' => $note]);
        notify($conn, 'Customer', $order['user_id'], 'order_status', "Order #{$order['order_id']} refunded", "$note.", '/my-orders', "order:{$order['order_id']}");
    }
    notify($conn, 'Admin', null, $refunded ? 'payment_refunded' : 'refund_needed',
        ($refunded ? 'Payment refunded: order #' : 'Refund needed: order #') . $order['order_id'],
        $reason . ($refunded ? '. Stripe refund was sent automatically.' : '. Automatic refund failed, please refund it in the Stripe dashboard.'),
        'orders', "refund:{$order['order_id']}");
    return $refunded ? 'refunded' : 'refund_failed';
}

function finalize_stripe_order($conn, $order_id, $session) {
    $own_transaction = !$conn->inTransaction();
    if ($own_transaction) $conn->beginTransaction();
    try {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = :oid FOR UPDATE");
        $stmt->execute([':oid' => $order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) throw new Exception("Order not found");

        if (in_array($order['payment_status'], ['Paid', 'Refunded'], true)) {
            $result = $order['status'] === 'Cancelled' ? 'refunded' : 'paid';
        } elseif ($order['status'] === 'Cancelled') {
            $result = refund_and_cancel($conn, $order, $session, 'Payment arrived after the order was cancelled');
        } else {
            $stmt = $conn->prepare("SELECT oi.product_id, oi.quantity, p.name, bs.stock
                                    FROM order_items oi
                                    JOIN products p ON p.product_id = oi.product_id
                                    LEFT JOIN branch_stock bs ON bs.product_id = oi.product_id AND bs.branch_id = :bid
                                    WHERE oi.order_id = :oid FOR UPDATE");
            $stmt->execute([':oid' => $order_id, ':bid' => $order['branch_id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $short = array_filter($items, fn($i) => $i['stock'] === null || (int)$i['stock'] < (int)$i['quantity']);
            if ($short) {
                $names = implode(', ', array_column($short, 'name'));
                $result = refund_and_cancel($conn, $order, $session, "Sorry, $names sold out before your payment was completed");
            } else {
                $upd = $conn->prepare("UPDATE branch_stock SET stock = stock - :qty WHERE product_id = :pid AND branch_id = :bid");
                $log = $conn->prepare("INSERT INTO stock_logs (product_id, branch_id, change_type, quantity_change) VALUES (:pid, :bid, 'Sale', :qty)");
                foreach ($items as $item) {
                    $upd->execute([':qty' => $item['quantity'], ':pid' => $item['product_id'], ':bid' => $order['branch_id']]);
                    $log->execute([':pid' => $item['product_id'], ':bid' => $order['branch_id'], ':qty' => -$item['quantity']]);
                }
                $conn->prepare("UPDATE orders SET payment_status = 'Paid', status = 'Pending', stock_deducted = 1 WHERE order_id = :oid")
                     ->execute([':oid' => $order_id]);
                record_status($conn, $order_id, 'Pending', 'Online payment received', 'Stripe');
                check_stock_levels($conn, $order['branch_id'], array_column($items, 'product_id'));
                $result = 'paid';
            }
        }
        if ($own_transaction) $conn->commit();
        return $result;
    } catch (Exception $e) {
        if ($own_transaction && $conn->inTransaction()) $conn->rollBack();
        throw $e;
    }
}

function cancel_unpaid_stripe_order($conn, $order, $note, $changed_by) {
    $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE order_id = :oid AND status = 'Awaiting Payment' AND payment_status = 'Unpaid'");
    $stmt->execute([':oid' => $order['order_id']]);
    if ($stmt->rowCount() > 0) {
        record_status($conn, $order['order_id'], 'Cancelled', $note, $changed_by);
        return true;
    }
    return false;
}

function settle_awaiting_order($conn, $order, $cancel_note, $changed_by, $force_cancel = false) {
    $session = null;
    try {
        $session = stripe_get_session($order['stripe_session_id']);
    } catch (Exception $e) {
        error_log("Stripe lookup failed: " . $e->getMessage());
        return 'unknown';
    }
    if ($session && ($session['payment_status'] ?? '') === 'paid') {
        return finalize_stripe_order($conn, $order['order_id'], $session);
    }
    if ($session && ($session['status'] ?? '') === 'open') {
        if (!$force_cancel) return 'open';
        stripe_expire_session($order['stripe_session_id']);
    }
    cancel_unpaid_stripe_order($conn, $order, $cancel_note, $changed_by);
    return 'cancelled';
}

function expire_stale_payments($conn) {
    $stmt = $conn->prepare("SELECT order_id, user_id, status, stripe_session_id FROM orders
                            WHERE status = 'Awaiting Payment' AND payment_status = 'Unpaid' AND payment_method = 'Stripe'
                            AND order_date < NOW() - INTERVAL :mins MINUTE");
    $stmt->bindValue(':mins', PAYMENT_WINDOW_MINUTES + 5, PDO::PARAM_INT);
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $order) {
        try {
            settle_awaiting_order($conn, $order, 'Online payment was not completed within ' . PAYMENT_WINDOW_MINUTES . ' minutes', 'System', true);
        } catch (Exception $e) {
            error_log("Could not settle order {$order['order_id']}: " . $e->getMessage());
        }
    }
}
?>
