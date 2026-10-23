<?php
session_start();
require_once '../config/database.php';
require_once '../config/checkout.php';
require_once '../config/payments.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Please login to place an order"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true) ?? [];

$customer_name = trim($data['customer_name'] ?? '');
$customer_phone = trim($data['customer_phone'] ?? '');

if ($customer_name === '' || $customer_phone === '') {
    echo json_encode(["success" => false, "message" => "Name and phone number are required"]);
    exit;
}

$phone_digits = preg_replace('/\D/', '', $customer_phone);
if (!preg_match('/^\+?[0-9\s-]+$/', $customer_phone) || strlen($phone_digits) < 9 || strlen($phone_digits) > 13) {
    echo json_encode(["success" => false, "message" => "Please enter a valid phone number, for example 012-345 6789"]);
    exit;
}

try {
    $checkout = build_checkout($conn, $data, true);
    $is_online = $checkout['payment_method'] === 'Stripe';
    $address = $checkout['address'];

    $conn->beginTransaction();

    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, customer_name, customer_phone, customer_address, delivery_type, payment_method,
                            street, city, state, postcode, distance_km, slot_date, slot_time,
                            status, total_amount, delivery_fee, sst_amount, branch_id, payment_status)
        VALUES (:uid, :name, :phone, :address, :dtype, :pmethod,
                :street, :city, :state, :postcode, :distance, :sdate, :stime,
                :status, :total, :fee, :sst, :bid, 'Unpaid')
    ");
    $stmt->execute([
        ':uid' => $_SESSION['user_id'],
        ':name' => $customer_name,
        ':phone' => $customer_phone,
        ':address' => format_address($address),
        ':dtype' => $checkout['delivery_type'],
        ':pmethod' => $checkout['payment_method'],
        ':street' => $address['street'],
        ':city' => $address['city'],
        ':state' => $address['state'],
        ':postcode' => $address['postcode'],
        ':distance' => $checkout['distance_km'],
        ':sdate' => $checkout['slot_date'],
        ':stime' => $checkout['slot_time'],
        ':status' => $is_online ? 'Awaiting Payment' : 'Pending',
        ':total' => $checkout['total'],
        ':fee' => $checkout['delivery_fee'],
        ':sst' => $checkout['sst'],
        ':bid' => $checkout['branch']['branch_id']
    ]);
    $order_id = $conn->lastInsertId();
    record_status($conn, $order_id, $is_online ? 'Awaiting Payment' : 'Pending',
        $is_online ? 'Order created, waiting for online payment' : 'Order placed', $customer_name);

    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, subtotal) VALUES (:oid, :pid, :qty, :subtotal)");
    foreach ($checkout['lines'] as $line) {
        $item_stmt->execute([
            ':oid' => $order_id,
            ':pid' => $line['product_id'],
            ':qty' => $line['quantity'],
            ':subtotal' => $line['subtotal']
        ]);
    }

    if (!$is_online) {
        $upd = $conn->prepare("UPDATE branch_stock SET stock = stock - :qty WHERE product_id = :pid AND branch_id = :bid AND stock >= :qty2");
        $log = $conn->prepare("INSERT INTO stock_logs (product_id, branch_id, change_type, quantity_change) VALUES (:pid, :bid, 'Sale', :qty)");
        foreach ($checkout['lines'] as $line) {
            $upd->execute([
                ':qty' => $line['quantity'],
                ':qty2' => $line['quantity'],
                ':pid' => $line['product_id'],
                ':bid' => $checkout['branch']['branch_id']
            ]);
            if ($upd->rowCount() === 0) throw new Exception("Not enough stock for {$line['name']}");
            $log->execute([':pid' => $line['product_id'], ':bid' => $checkout['branch']['branch_id'], ':qty' => -$line['quantity']]);
        }
        $conn->prepare("UPDATE orders SET stock_deducted = 1 WHERE order_id = :oid")->execute([':oid' => $order_id]);
        check_stock_levels($conn, $checkout['branch']['branch_id'], array_column($checkout['lines'], 'product_id'));
    }

    if (!empty($data['save_details'])) {
        $profile = $conn->prepare("UPDATE users SET phone = :phone" . ($address['street'] ? ", street = :street, city = :city, state = :state, postcode = :postcode" : "") . " WHERE user_id = :uid");
        $params = [':phone' => $customer_phone, ':uid' => $_SESSION['user_id']];
        if ($address['street']) {
            $params += [':street' => $address['street'], ':city' => $address['city'], ':state' => $address['state'], ':postcode' => $address['postcode']];
        }
        $profile->execute($params);
    }

    $conn->commit();

    if (!$is_online) {
        echo json_encode([
            "success" => true,
            "order_id" => $order_id,
            "total_amount" => $checkout['total'],
            "redirect_url" => FRONTEND_URL . "/my-orders?placed=" . $order_id
        ]);
        exit;
    }

    $line_items = [];
    foreach ($checkout['lines'] as $line) {
        $line_items[] = [
            'price_data' => [
                'currency' => 'myr',
                'product_data' => ['name' => $line['name']],
                'unit_amount' => (int) round($line['unit_price'] * 100),
            ],
            'quantity' => $line['quantity'],
        ];
    }
    $line_items[] = [
        'price_data' => [
            'currency' => 'myr',
            'product_data' => ['name' => 'SST ' . round(SST_RATE * 100) . '%'],
            'unit_amount' => (int) round($checkout['sst'] * 100),
        ],
        'quantity' => 1,
    ];
    if ($checkout['delivery_fee'] > 0) {
        $line_items[] = [
            'price_data' => [
                'currency' => 'myr',
                'product_data' => ['name' => 'Delivery fee (' . number_format($checkout['distance_km'], 1) . ' km)'],
                'unit_amount' => (int) round($checkout['delivery_fee'] * 100),
            ],
            'quantity' => 1,
        ];
    }

    $session = stripe_request('POST', 'checkout/sessions', [
        'mode' => 'payment',
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'client_reference_id' => $order_id,
        'success_url' => BACKEND_URL . '/payment/confirm_payment.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => BACKEND_URL . '/payment/cancel_payment.php?order_id=' . $order_id,
        'expires_at' => time() + (PAYMENT_WINDOW_MINUTES + 1) * 60,
    ]);

    if (!isset($session['url'])) {
        cancel_unpaid_stripe_order($conn, ['order_id' => $order_id], 'Could not start the online payment', 'System');
        throw new Exception("Stripe error: " . ($session['error']['message'] ?? 'unknown error'));
    }

    $conn->prepare("UPDATE orders SET stripe_session_id = :sid WHERE order_id = :oid")
         ->execute([':sid' => $session['id'], ':oid' => $order_id]);

    echo json_encode([
        "success" => true,
        "order_id" => $order_id,
        "total_amount" => $checkout['total'],
        "checkout_url" => $session['url']
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
