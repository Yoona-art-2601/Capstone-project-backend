<?php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['product_id']) || !isset($data['from_branch_id']) || !isset($data['to_branch_id']) || !isset($data['quantity'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields (product_id, from_branch_id, to_branch_id, quantity)"
    ]);
    exit;
}

$product_id = $data['product_id'];
$from_branch = $data['from_branch_id'];
$to_branch = $data['to_branch_id'];
$quantity = $data['quantity'];

if ($from_branch == $to_branch) {
    echo json_encode([
        "success" => false,
        "message" => "from_branch_id and to_branch_id cannot be the same"
    ]);
    exit;
}

try {
    $conn->beginTransaction();

    // check stock in source branch
    $stmt = $conn->prepare("SELECT stock FROM branch_stock WHERE product_id = :pid AND branch_id = :bid");
    $stmt->execute([':pid' => $product_id, ':bid' => $from_branch]);
    $source = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$source) {
        throw new Exception("Product not found in source branch");
    }
    if ($source['stock'] < $quantity) {
        throw new Exception("Insufficient stock in source branch for transfer");
    }

    // see stock in destination branch
    $stmt = $conn->prepare("UPDATE branch_stock SET stock = stock - :qty WHERE product_id = :pid AND branch_id = :bid");
    $stmt->execute([':qty' => $quantity, ':pid' => $product_id, ':bid' => $from_branch]);

    // check stock in destination branch
    $stmt = $conn->prepare("UPDATE branch_stock SET stock = stock + :qty WHERE product_id = :pid AND branch_id = :bid");
    $stmt->execute([':qty' => $quantity, ':pid' => $product_id, ':bid' => $to_branch]);

    // write stock transfer record to stock_transfers table
    $stmt = $conn->prepare("INSERT INTO stock_transfers (product_id, from_branch_id, to_branch_id, quantity) 
                             VALUES (:pid, :from_bid, :to_bid, :qty)");
    $stmt->execute([
        ':pid' => $product_id,
        ':from_bid' => $from_branch,
        ':to_bid' => $to_branch,
        ':qty' => $quantity
    ]);

    // ⑤ write stock_logs for both branches
    $stmt = $conn->prepare("INSERT INTO stock_logs (product_id, branch_id, change_type, quantity_change) 
                             VALUES (:pid, :bid, 'Transfer Out', :qty)");
    $stmt->execute([':pid' => $product_id, ':bid' => $from_branch, ':qty' => -$quantity]);

    $stmt = $conn->prepare("INSERT INTO stock_logs (product_id, branch_id, change_type, quantity_change) 
                             VALUES (:pid, :bid, 'Transfer In', :qty)");
    $stmt->execute([':pid' => $product_id, ':bid' => $to_branch, ':qty' => $quantity]);

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Stock transferred successfully"
    ]);

} catch(Exception $e) {
    $conn->rollBack();
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>