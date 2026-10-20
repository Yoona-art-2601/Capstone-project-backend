<?php
require_once '../config/database.php';
require_once '../config/stripe.php';
require_once '../config/order_flow.php';

$session_id = $_GET['session_id'] ?? null;

if (!$session_id) {
    die("Missing session_id");
}

try {
    
    $session = stripe_request('GET', 'checkout/sessions/' . urlencode($session_id));

    if (!isset($session['payment_status'])) {
        throw new Exception("Could not verify payment with Stripe");
    }

    if ($session['payment_status'] !== 'paid') {
        throw new Exception("Payment not completed");
    }

    
    $stmt = $conn->prepare("SELECT * FROM orders WHERE stripe_session_id = :sid");
    $stmt->execute([':sid' => $session_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception("Order not found");
    }

    
    if ($order['payment_status'] === 'Paid') {
        header("Location: " . FRONTEND_URL . "/?payment=success&order_id=" . $order['order_id']);
        exit;
    }

    $conn->beginTransaction();

    
    $stmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = :oid");
    $stmt->execute([':oid' => $order['order_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $upd = $conn->prepare("UPDATE branch_stock
                                SET stock = stock - :qty
                                WHERE product_id = :pid AND branch_id = :bid AND stock >= :qty2");
        $upd->execute([
            ':qty' => $item['quantity'],
            ':pid' => $item['product_id'],
            ':bid' => $order['branch_id'],
            ':qty2' => $item['quantity']
        ]);

        if ($upd->rowCount() === 0) {
            
            throw new Exception("Stock no longer available for product ID {$item['product_id']}. Please contact the store for a refund.");
        }

        $log = $conn->prepare("INSERT INTO stock_logs (product_id, branch_id, change_type, quantity_change)
                                VALUES (:pid, :bid, 'Sale', :qty)");
        $log->execute([
            ':pid' => $item['product_id'],
            ':bid' => $order['branch_id'],
            ':qty' => -$item['quantity']
        ]);
    }

    
    $stmt = $conn->prepare("UPDATE orders SET payment_status = 'Paid', status = 'Pending', stock_deducted = 1 WHERE order_id = :oid");
    $stmt->execute([':oid' => $order['order_id']]);
    record_status($conn, $order['order_id'], 'Pending', 'Online payment received', 'Stripe');

    $conn->commit();

    header("Location: " . FRONTEND_URL . "/?payment=success&order_id=" . $order['order_id']);
    exit;

} catch(Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "<h2>Payment confirmation error</h2><p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>