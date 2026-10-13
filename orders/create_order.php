<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';

$data = json_decode(file_get_contents("php://input"), true);

// check customer_name, branch_id, and items
if (!isset($data['branch_id']) || !isset($data['customer_name']) || !isset($data['items']) || count($data['items']) === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Missing branch_id, customer_name or items"
    ]);
    exit;
}

$branch_id = $data['branch_id'];

try {
    $conn->beginTransaction();

    // check stock availability for each item in the order
    foreach ($data['items'] as $item) {
        $stmt = $conn->prepare("
            SELECT bs.stock, p.price 
            FROM branch_stock bs
            JOIN products p ON bs.product_id = p.product_id
            WHERE bs.product_id = :pid AND bs.branch_id = :bid
        ");
        $stmt->execute([':pid' => $item['product_id'], ':bid' => $branch_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Product ID {$item['product_id']} not found in this branch");
        }
        if ($product['stock'] < $item['quantity']) {
            throw new Exception("Insufficient stock for product ID {$item['product_id']} in this branch");
        }
    }

    // calculate total amount
    $total_amount = 0;
    foreach ($data['items'] as $item) {
        $stmt = $conn->prepare("SELECT price FROM products WHERE product_id = :pid");
        $stmt->execute([':pid' => $item['product_id']]);
        $price = $stmt->fetch(PDO::FETCH_ASSOC)['price'];
        $total_amount += $price * $item['quantity'];
    }

    // write order to orders table
    $stmt = $conn->prepare("INSERT INTO orders (customer_name, customer_phone, status, total_amount, branch_id) 
                             VALUES (:name, :phone, 'Pending', :total, :bid)");
    $stmt->execute([
        ':name' => $data['customer_name'],
        ':phone' => $data['customer_phone'] ?? null,
        ':total' => $total_amount,
        ':bid' => $branch_id
    ]);
    $order_id = $conn->lastInsertId();

    // write order_items, update branch_stock, and write stock_logs
    foreach ($data['items'] as $item) {
        $stmt = $conn->prepare("SELECT price FROM products WHERE product_id = :pid");
        $stmt->execute([':pid' => $item['product_id']]);
        $price = $stmt->fetch(PDO::FETCH_ASSOC)['price'];
        $subtotal = $price * $item['quantity'];

        // write order_items
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, subtotal) 
                                 VALUES (:oid, :pid, :qty, :subtotal)");
        $stmt->execute([
            ':oid' => $order_id,
            ':pid' => $item['product_id'],
            ':qty' => $item['quantity'],
            ':subtotal' => $subtotal
        ]);

        // 扣减该分店的库存
        $stmt = $conn->prepare("UPDATE branch_stock 
                                 SET stock = stock - :qty 
                                 WHERE product_id = :pid AND branch_id = :bid");
        $stmt->execute([
            ':qty' => $item['quantity'],
            ':pid' => $item['product_id'],
            ':bid' => $branch_id
        ]);

        // 写入 stock_logs（含分店，负数代表扣减）
        $stmt = $conn->prepare("INSERT INTO stock_logs (product_id, branch_id, change_type, quantity_change) 
                                 VALUES (:pid, :bid, 'Sale', :qty)");
        $stmt->execute([
            ':pid' => $item['product_id'],
            ':bid' => $branch_id,
            ':qty' => -$item['quantity']
        ]);
    }

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Order created successfully",
        "order_id" => $order_id,
        "total_amount" => $total_amount,
        "branch_id" => $branch_id
    ]);

} catch(Exception $e) {
    $conn->rollBack();
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>