<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/audit.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['product_id']) || !isset($data['from_branch_id']) || !isset($data['to_branch_id']) || !isset($data['quantity'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields (product_id, from_branch_id, to_branch_id, quantity)"
    ]);
    exit;
}

if (!is_numeric($data['quantity']) || (int)$data['quantity'] <= 0) {
    echo json_encode(["success" => false, "message" => "Quantity must be a positive whole number"]);
    exit;
}

$product_id = (int)$data['product_id'];
$from_branch = (int)$data['from_branch_id'];
$to_branch = (int)$data['to_branch_id'];
$quantity = (int)$data['quantity'];

if ($from_branch === $to_branch) {
    echo json_encode(["success" => false, "message" => "Source and destination branch must be different"]);
    exit;
}

try {
    $conn->beginTransaction();

    
    $stmt = $conn->prepare("SELECT stock FROM branch_stock WHERE product_id = :pid AND branch_id = :bid FOR UPDATE");
    $stmt->execute([':pid' => $product_id, ':bid' => $from_branch]);
    $source = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$source) {
        throw new Exception("Product not found in source branch");
    }
    if ($source['stock'] < $quantity) {
        throw new Exception("Insufficient stock in source branch for transfer");
    }

    // Make sure the destination row exists
    $stmt = $conn->prepare("SELECT branch_stock_id FROM branch_stock WHERE product_id = :pid AND branch_id = :bid FOR UPDATE");
    $stmt->execute([':pid' => $product_id, ':bid' => $to_branch]);
    if (!$stmt->fetch()) {
        throw new Exception("Product not found in destination branch");
    }

    
    $stmt = $conn->prepare("UPDATE branch_stock SET stock = stock - :qty WHERE product_id = :pid AND branch_id = :bid");
    $stmt->execute([':qty' => $quantity, ':pid' => $product_id, ':bid' => $from_branch]);

    $stmt = $conn->prepare("UPDATE branch_stock SET stock = stock + :qty WHERE product_id = :pid AND branch_id = :bid");
    $stmt->execute([':qty' => $quantity, ':pid' => $product_id, ':bid' => $to_branch]);

    
    $stmt = $conn->prepare("INSERT INTO stock_transfers (product_id, from_branch_id, to_branch_id, quantity)
                             VALUES (:pid, :from_bid, :to_bid, :qty)");
    $stmt->execute([
        ':pid' => $product_id,
        ':from_bid' => $from_branch,
        ':to_bid' => $to_branch,
        ':qty' => $quantity
    ]);

    
    $stmt = $conn->prepare("INSERT INTO stock_logs (product_id, branch_id, change_type, quantity_change)
                             VALUES (:pid, :bid, 'Transfer Out', :qty)");
    $stmt->execute([':pid' => $product_id, ':bid' => $from_branch, ':qty' => -$quantity]);

    $stmt = $conn->prepare("INSERT INTO stock_logs (product_id, branch_id, change_type, quantity_change)
                             VALUES (:pid, :bid, 'Transfer In', :qty)");
    $stmt->execute([':pid' => $product_id, ':bid' => $to_branch, ':qty' => $quantity]);

    audit_log($conn, 'STOCK_TRANSFER', 'product', $product_id,
        "Transferred $quantity x " . product_name($conn, $product_id) . " from " . branch_name($conn, $from_branch)
        . " to " . branch_name($conn, $to_branch));

    $conn->commit();

    echo json_encode(["success" => true, "message" => "Stock transferred successfully"]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>