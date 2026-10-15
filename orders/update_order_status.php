<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/audit.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['order_id']) || !isset($data['status'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields (order_id, status)"]);
    exit;
}

$allowed_status = ['Pending', 'Processing', 'Completed', 'Cancelled'];
if (!in_array($data['status'], $allowed_status)) {
    echo json_encode(["success" => false, "message" => "Invalid status. Allowed: Pending, Processing, Completed, Cancelled"]);
    exit;
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("SELECT status, payment_status, branch_id FROM orders WHERE order_id = :oid FOR UPDATE");
    $stmt->execute([':oid' => $data['order_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception("Order not found");
    }
    if ($order['status'] === 'Cancelled') {
        throw new Exception("Cancelled orders cannot be changed");
    }
    if ($order['status'] === 'Awaiting Payment') {
        throw new Exception("This order is still awaiting payment");
    }

    if ($order['status'] !== $data['status']) {

        // Cancelling a paid order: return the stock to the branch (FR-15)
        if ($data['status'] === 'Cancelled' && $order['payment_status'] === 'Paid' && $order['branch_id'] !== null) {
            $stmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = :oid");
            $stmt->execute([':oid' => $data['order_id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                $upd = $conn->prepare("UPDATE branch_stock SET stock = stock + :qty WHERE product_id = :pid AND branch_id = :bid");
                $upd->execute([
                    ':qty' => $item['quantity'],
                    ':pid' => $item['product_id'],
                    ':bid' => $order['branch_id']
                ]);

                $log = $conn->prepare("INSERT INTO stock_logs (product_id, branch_id, change_type, quantity_change)
                                        VALUES (:pid, :bid, 'Cancel Return', :qty)");
                $log->execute([
                    ':pid' => $item['product_id'],
                    ':bid' => $order['branch_id'],
                    ':qty' => $item['quantity']
                ]);
            }
        }

        $stmt = $conn->prepare("UPDATE orders SET status = :status WHERE order_id = :oid");
        $stmt->execute([':status' => $data['status'], ':oid' => $data['order_id']]);

        $returned = ($data['status'] === 'Cancelled' && $order['payment_status'] === 'Paid' && $order['branch_id'] !== null)
            ? ' (stock returned to branch)' : '';
        audit_log($conn, 'ORDER_STATUS', 'order', $data['order_id'],
            "Order #{$data['order_id']}: {$order['status']} -> {$data['status']}$returned");
    }

    $conn->commit();

    echo json_encode(["success" => true, "message" => "Order status updated successfully"]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>