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
?>
