<?php
require_once '../config/database.php';
require_once '../config/stripe.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['branch_id']) || !isset($data['customer_name']) || !isset($data['items']) || count($data['items']) === 0) {
    echo json_encode(["success" => false, "message" => "Missing branch_id, customer_name or items"]);
    exit;
}

$branch_id = $data['branch_id'];

try {
    $conn->beginTransaction();

    $line_items = [];
    $order_lines = [];
    $total = 0;

    // check stock for each item in the order
    foreach ($data['items'] as $item) {
        $stmt = $conn->prepare("
            SELECT p.name, p.price, bs.stock
            FROM products p
            JOIN branch_stock bs ON p.product_id = bs.product_id
            WHERE p.product_id = :pid AND bs.branch_id = :bid AND p.is_active = 1
        ");
        $stmt->execute([':pid' => $item['product_id'], ':bid' => $branch_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Product ID {$item['product_id']} not found in this branch");
        }
        if ($product['stock'] < $item['quantity']) {
            throw new Exception("Insufficient stock for {$product['name']}");
        }

        $subtotal = $product['price'] * $item['quantity'];
        $total += $subtotal;

        $order_lines[] = [
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'subtotal' => $subtotal
        ];

        
        $line_items[] = [
            'price_data' => [
                'currency' => 'myr',
                'product_data' => ['name' => $product['name']],
                'unit_amount' => (int) round($product['price'] * 100),
            ],
            'quantity' => $item['quantity'],
        ];
    }

    // ② create order in orders table
    $stmt = $conn->prepare("INSERT INTO orders (customer_name, customer_phone, status, total_amount, branch_id, payment_status)
                             VALUES (:name, :phone, 'Awaiting Payment', :total, :bid, 'Unpaid')");
    $stmt->execute([
        ':name' => $data['customer_name'],
        ':phone' => $data['customer_phone'] ?? null,
        ':total' => $total,
        ':bid' => $branch_id
    ]);
    $order_id = $conn->lastInsertId();

    // order
    foreach ($order_lines as $line) {
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, subtotal)
                                 VALUES (:oid, :pid, :qty, :subtotal)");
        $stmt->execute([
            ':oid' => $order_id,
            ':pid' => $line['product_id'],
            ':qty' => $line['quantity'],
            ':subtotal' => $line['subtotal']
        ]);
    }

    $conn->commit();

    
    $session = stripe_request('POST', 'checkout/sessions', [
        'mode' => 'payment',
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'client_reference_id' => $order_id,
        'success_url' => BACKEND_URL . '/payment/confirm_payment.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => BACKEND_URL . '/payment/cancel_payment.php?order_id=' . $order_id,
    ]);

    if (!isset($session['url'])) {
        // fail to create Stripe session, cancel the order
        $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE order_id = :oid")->execute([':oid' => $order_id]);
        throw new Exception("Stripe error: " . ($session['error']['message'] ?? 'unknown error'));
    }

    
    $stmt = $conn->prepare("UPDATE orders SET stripe_session_id = :sid WHERE order_id = :oid");
    $stmt->execute([':sid' => $session['id'], ':oid' => $order_id]);

    echo json_encode([
        "success" => true,
        "order_id" => $order_id,
        "total_amount" => $total,
        "checkout_url" => $session['url']
    ]);

} catch(Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>