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

if (!is_numeric($data['quantity_change']) || (int)$data['quantity_change'] === 0) {
    echo json_encode(["success" => false, "message" => "Quantity must be a non-zero number"]);
    exit;
}

$qty = (int)$data['quantity_change'];

try {
    $conn->beginTransaction();

    // Only update if the stock will not go below zero
    $stmt1 = $conn->prepare("UPDATE branch_stock
                              SET stock = stock + :qty
                              WHERE branch_id = :bid AND product_id = :pid AND stock + :qty2 >= 0");
    $stmt1->execute([
        ':qty' => $qty,
        ':qty2' => $qty,
        ':bid' => $data['branch_id'],
        ':pid' => $data['product_id']
    ]);

    if ($stmt1->rowCount() === 0) {
        throw new Exception("Stock cannot go below zero (or product not found in this branch)");
    }

    $stmt2 = $conn->prepare("INSERT INTO stock_logs (product_id, branch_id, change_type, quantity_change)
                              VALUES (:pid, :bid, :type, :qty)");
    $stmt2->execute([
        ':pid' => $data['product_id'],
        ':bid' => $data['branch_id'],
        ':type' => $data['change_type'],
        ':qty' => $qty
    ]);

    $conn->commit();

    echo json_encode(["success" => true, "message" => "Stock updated successfully"]);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>