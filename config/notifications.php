<?php
function notify($conn, $audience, $user_id, $type, $title, $message, $link = null, $ref_key = null) {
    $conn->prepare("INSERT INTO notifications (audience, user_id, type, title, message, link, ref_key)
                    VALUES (:aud, :uid, :type, :title, :msg, :link, :ref)")
         ->execute([
             ':aud' => $audience, ':uid' => $user_id, ':type' => $type, ':title' => $title,
             ':msg' => $message, ':link' => $link, ':ref' => $ref_key
         ]);
}

function check_stock_levels($conn, $branch_id, $product_ids) {
    $stmt = $conn->prepare("
        SELECT bs.stock, bs.low_stock_threshold, p.product_id, p.name, b.branch_name
        FROM branch_stock bs
        JOIN products p ON p.product_id = bs.product_id
        JOIN branches b ON b.branch_id = bs.branch_id
        WHERE bs.branch_id = :bid AND bs.product_id = :pid AND p.is_active = 1
    ");
    $unread = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE audience = 'Admin' AND ref_key = :ref AND is_read = 0");
    $alerts = $conn->prepare("SELECT alert_id, user_id FROM stock_alerts WHERE product_id = :pid AND branch_id = :bid AND notified_at IS NULL");
    $mark = $conn->prepare("UPDATE stock_alerts SET notified_at = NOW() WHERE alert_id = :aid");

    foreach (array_unique(array_map('intval', $product_ids)) as $product_id) {
        $stmt->execute([':bid' => $branch_id, ':pid' => $product_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) continue;
        $stock = (int)$row['stock'];

        if ($stock <= (int)$row['low_stock_threshold']) {
            $ref = "low_stock:$branch_id:$product_id";
            $unread->execute([':ref' => $ref]);
            if ((int)$unread->fetchColumn() === 0) {
                $out = $stock <= 0;
                notify($conn, 'Admin', null, $out ? 'out_of_stock' : 'low_stock',
                    ($out ? 'Out of stock: ' : 'Low stock: ') . $row['name'],
                    "{$row['branch_name']} has $stock left (alert level {$row['low_stock_threshold']}). Please restock.",
                    'inventory?branch=' . $branch_id, $ref);
            }
        }

        if ($stock > 0) {
            $alerts->execute([':pid' => $product_id, ':bid' => $branch_id]);
            foreach ($alerts->fetchAll(PDO::FETCH_ASSOC) as $alert) {
                notify($conn, 'Customer', $alert['user_id'], 'back_in_stock',
                    'Back in stock: ' . $row['name'],
                    "{$row['name']} is available again at {$row['branch_name']}. Order before it runs out.",
                    '/?branch=' . $branch_id, "back_in_stock:$branch_id:$product_id");
                $mark->execute([':aid' => $alert['alert_id']]);
            }
        }
    }
}

function order_product_ids($conn, $order_id) {
    $stmt = $conn->prepare("SELECT product_id FROM order_items WHERE order_id = :oid");
    $stmt->execute([':oid' => $order_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function notification_scope($scope) {
    if ($scope === 'admin') {
        if (($_SESSION['role'] ?? '') !== 'Admin') {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Access denied. Admin only"]);
            exit;
        }
        return ["audience = 'Admin'", []];
    }
    return ["audience = 'Customer' AND user_id = :uid", [':uid' => $_SESSION['user_id']]];
}

function notify_admin_order($conn, $order_id, $event) {
    $stmt = $conn->prepare("SELECT o.order_id, o.customer_name, o.total_amount, o.payment_method, o.delivery_type, o.slot_date, o.slot_time,
                                   o.branch_id, b.branch_name, (SELECT SUM(quantity) FROM order_items WHERE order_id = o.order_id) AS units
                            FROM orders o LEFT JOIN branches b ON b.branch_id = o.branch_id WHERE o.order_id = :oid");
    $stmt->execute([':oid' => $order_id]);
    $o = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$o) return;

    $payments = ['Stripe' => 'paid online', 'COD' => 'cash on delivery', 'PayAtStore' => 'pay at store'];
    $amount = 'RM ' . number_format((float)$o['total_amount'], 2);
    $fulfil = ($o['delivery_type'] === 'Pickup' ? 'Pickup' : 'Delivery') . ($o['slot_date'] ? " on {$o['slot_date']} {$o['slot_time']}" : '');
    $link = 'orders?branch=' . $o['branch_id'];

    if ($event === 'new') {
        notify($conn, 'Admin', null, 'new_order',
            "New order #{$o['order_id']} at {$o['branch_name']}",
            "{$o['customer_name']}, {$o['units']} item(s), $amount ({$payments[$o['payment_method']]}). $fulfil. Please prepare it.",
            $link, "new_order:{$o['order_id']}");
    } elseif ($event === 'customer_cancelled') {
        notify($conn, 'Admin', null, 'order_cancelled',
            "Order #{$o['order_id']} cancelled by customer",
            "{$o['customer_name']} cancelled their $amount order at {$o['branch_name']}. The items are back in stock, no need to prepare it.",
            $link, "order_cancelled:{$o['order_id']}");
    }
}
?>
