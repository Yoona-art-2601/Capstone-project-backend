<?php
require_once __DIR__ . '/shop.php';
require_once __DIR__ . '/geo.php';

function build_checkout($conn, $data, $require_slot = true) {
    if (!isset($data['branch_id']) || !isset($data['items']) || !is_array($data['items']) || count($data['items']) === 0) {
        throw new Exception("Your cart is empty");
    }

    $stmt = $conn->prepare("SELECT branch_id, branch_name, opening_hours, latitude, longitude FROM branches WHERE branch_id = :bid");
    $stmt->execute([':bid' => $data['branch_id']]);
    $branch = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$branch) throw new Exception("Branch not found");

    $delivery_type = $data['delivery_type'] ?? 'Delivery';
    if (!in_array($delivery_type, ['Delivery', 'Pickup'], true)) throw new Exception("Invalid delivery type");

    $payment_method = $data['payment_method'] ?? 'Stripe';
    $allowed_payments = $delivery_type === 'Delivery' ? ['Stripe', 'COD'] : ['Stripe', 'PayAtStore'];
    if (!in_array($payment_method, $allowed_payments, true)) throw new Exception("This payment method is not available for " . strtolower($delivery_type));

    $lines = [];
    $subtotal = 0;
    foreach ($data['items'] as $item) {
        $qty = (int)($item['quantity'] ?? 0);
        if ($qty <= 0) throw new Exception("Invalid quantity");
        $stmt = $conn->prepare("
            SELECT p.product_id, p.name, p.price, p.sale_price, bs.stock
            FROM products p
            JOIN branch_stock bs ON p.product_id = bs.product_id
            WHERE p.product_id = :pid AND bs.branch_id = :bid AND p.is_active = 1
        ");
        $stmt->execute([':pid' => $item['product_id'], ':bid' => $branch['branch_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) throw new Exception("A product in your cart is no longer sold at this branch");
        if ($product['stock'] < $qty) throw new Exception("Only {$product['stock']} of {$product['name']} left at this branch");

        $unit = effective_price($product['price'], $product['sale_price']);
        $lines[] = [
            'product_id' => (int)$product['product_id'],
            'name' => $product['name'],
            'quantity' => $qty,
            'unit_price' => $unit,
            'subtotal' => round($unit * $qty, 2)
        ];
        $subtotal += $unit * $qty;
    }
    $subtotal = round($subtotal, 2);

    $address = ['street' => null, 'city' => null, 'state' => null, 'postcode' => null];
    $distance = null;
    $delivery_fee = 0.00;

    if ($delivery_type === 'Delivery') {
        foreach (['street', 'city', 'state', 'postcode'] as $field) {
            $address[$field] = trim((string)($data[$field] ?? ''));
        }
        if (strlen($address['street']) < 5 || $address['city'] === '' || $address['state'] === '') {
            throw new Exception("Please fill in your street, city and state");
        }
        if (!in_array($address['state'], MALAYSIA_STATES, true)) throw new Exception("Please choose a valid state");
        if (!preg_match('/^\d{5}$/', $address['postcode'])) throw new Exception("Postcode must be 5 digits");
        if ($branch['latitude'] === null) throw new Exception("This branch does not offer delivery yet");

        $point = geocode_address($conn, $address['street'], $address['city'], $address['state'], $address['postcode']);
        if (!$point) throw new Exception("We could not find this address on the map. Please check the postcode and city");

        $distance = round(haversine_km((float)$branch['latitude'], (float)$branch['longitude'], $point['lat'], $point['lng']), 2);
        if ($distance > DELIVERY_MAX_KM) {
            throw new Exception("This address is " . number_format($distance, 1) . " km from {$branch['branch_name']}. We deliver within " . DELIVERY_MAX_KM . " km. Choose another branch or self pickup");
        }
        $delivery_fee = delivery_fee_for_distance($subtotal, $distance);
    }

    $slot_date = $data['slot_date'] ?? null;
    $slot_time = $data['slot_time'] ?? null;
    if ($require_slot && !slot_is_available($branch['opening_hours'], (string)$slot_date, (string)$slot_time)) {
        throw new Exception("Please choose an available " . ($delivery_type === 'Delivery' ? 'delivery' : 'pickup') . " time slot");
    }

    $sst = sst_for($subtotal);
    return [
        'branch' => $branch,
        'delivery_type' => $delivery_type,
        'payment_method' => $payment_method,
        'lines' => $lines,
        'subtotal' => $subtotal,
        'sst' => $sst,
        'delivery_fee' => $delivery_fee,
        'distance_km' => $distance,
        'total' => round($subtotal + $sst + $delivery_fee, 2),
        'address' => $address,
        'slot_date' => $slot_date,
        'slot_time' => $slot_time
    ];
}

function format_address($address) {
    if (!$address['street']) return null;
    return $address['street'] . ', ' . $address['postcode'] . ' ' . $address['city'] . ', ' . $address['state'];
}
?>
