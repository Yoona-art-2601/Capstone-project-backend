<?php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['branch_id']) || !isset($data['product_id']) || !isset($data['quantity_change']) || !isset($data['change_type'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields (branch_id, product_id, quantity_change, change_type)"
    ]);
    exit;
}

try {
    $conn->beginTransaction();

    // update stock in branch_stock table
    $stmt1 = $conn->prepare("UPDATE branch_stock 
                              SET stock = stock + :qty 
                              WHERE branch_id = :bid AND product_id = :pid");
    $stmt1->execute([
        ':qty' => $data['quantity_change'],
        ':bid' => $data['branch_id'],
        ':pid' => $data['product_id']
    ]);

    // write a log entry to stock_logs table
    $stmt2 = $conn->prepare("INSERT INTO stock_logs (product_id, branch_id, change_type, quantity_change) 
                              VALUES (:pid, :bid, :type, :qty)");
    $stmt2->execute([
        ':pid' => $data['product_id'],
        ':bid' => $data['branch_id'],
        ':type' => $data['change_type'],
        ':qty' => $data['quantity_change']
    ]);

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Stock updated successfully"
    ]);
} catch(PDOException $e) {
    $conn->rollBack();
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>