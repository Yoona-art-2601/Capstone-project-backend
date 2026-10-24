<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/audit.php';
require_once '../config/order_flow.php';
require_once '../config/shop.php';

$data = json_decode(file_get_contents("php://input"), true) ?? [];

$branch_id = (int)($data['branch_id'] ?? 0);
$customer_name = trim((string)($data['customer_name'] ?? ''));
$customer_phone = trim((string)($data['customer_phone'] ?? ''));

$quantities = [];
foreach (is_array($data['items'] ?? null) ? $data['items'] : [] as $item) {
    $pid = (int)($item['product_id'] ?? 0);
    $qty = (int)($item['quantity'] ?? 0);
    if ($pid <= 0 || $qty <= 0) {
        echo json_encode(["success" => false, "message" => "Each item needs a product_id and a quantity of at least 1"]);
        exit;
    }
    $quantities[$pid] = ($quantities[$pid] ?? 0) + $qty;
}

if ($branch_id <= 0 || $customer_name === '' || !$quantities) {
    echo json_encode(["success" => false, "message" => "Missing branch_id, customer_name or items"]);
    exit;
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("
        SELECT p.product_id, p.name, p.price, " . ACTIVE_SALE_SQL . " AS sale_price, bs.stock
        FROM products p
        JOIN branch_stock bs ON bs.product_id = p.product_id AND bs.branch_id = :bid
        WHERE p.product_id = :pid AND p.is_active = 1
        FOR UPDATE
    ");
    $lines = [];
    $subtotal = 0;
    foreach ($quantities as $pid => $qty) {
        $stmt->execute([':pid' => $pid, ':bid' => $branch_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) throw new Exception("Product ID $pid is not sold at this branch");
        if ((int)$product['stock'] < $qty) throw new Exception("Only {$product['stock']} of {$product['name']} left at this branch");
        $line_total = round(effective_price($product['price'], $product['sale_price']) * $qty, 2);
        $lines[] = ['product_id' => $pid, 'quantity' => $qty, 'subtotal' => $line_total];
        $subtotal += $line_total;
    }
    $subtotal = round($subtotal, 2);
    $sst = sst_for($subtotal);
    $total = round($subtotal + $sst, 2);

    $conn->prepare("INSERT INTO orders (customer_name, customer_phone, delivery_type, payment_method, status, payment_status,
                                        total_amount, delivery_fee, sst_amount, stock_deducted, branch_id)
                    VALUES (:name, :phone, 'Pickup', 'PayAtStore', 'Pending', 'Unpaid', :total, 0, :sst, 1, :bid)")
         ->execute([
             ':name' => $customer_name,
             ':phone' => $customer_phone === '' ? null : $customer_phone,
             ':total' => $total,
             ':sst' => $sst,
             ':bid' => $branch_id
         ]);
    $order_id = $conn->lastInsertId();

    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, subtotal) VALUES (:oid, :pid, :qty, :subtotal)");
    $upd = $conn->prepare("UPDATE branch_stock SET stock = stock - :qty WHERE product_id = :pid AND branch_id = :bid");
    $log = $conn->prepare("INSERT INTO stock_logs (product_id, branch_id, change_type, quantity_change) VALUES (:pid, :bid, 'Sale', :qty)");
    foreach ($lines as $line) {
        $item_stmt->execute([':oid' => $order_id, ':pid' => $line['product_id'], ':qty' => $line['quantity'], ':subtotal' => $line['subtotal']]);
        $upd->execute([':qty' => $line['quantity'], ':pid' => $line['product_id'], ':bid' => $branch_id]);
        $log->execute([':pid' => $line['product_id'], ':bid' => $branch_id, ':qty' => -$line['quantity']]);
    }

    record_status($conn, $order_id, 'Pending', 'Order placed by staff', $_SESSION['name'] ?? 'Admin');
    audit_log($conn, 'ORDER_CREATE', 'order', $order_id,
        "Created order #$order_id for $customer_name at " . branch_name($conn, $branch_id) . " (RM " . number_format($total, 2) . ")");
    check_stock_levels($conn, $branch_id, array_keys($quantities));

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Order created successfully",
        "order_id" => $order_id,
        "subtotal" => $subtotal,
        "sst_amount" => $sst,
        "total_amount" => $total,
        "branch_id" => $branch_id
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
