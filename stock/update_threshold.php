<?php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['branch_id']) || !isset($data['product_id']) || !isset($data['low_stock_threshold'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields (branch_id, product_id, low_stock_threshold)"]);
    exit;
}

if (!is_numeric($data['low_stock_threshold']) || $data['low_stock_threshold'] < 0) {
    echo json_encode(["success" => false, "message" => "Threshold must be 0 or more"]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT branch_stock_id FROM branch_stock WHERE branch_id = :bid AND product_id = :pid");
    $stmt->execute([':bid' => $data['branch_id'], ':pid' => $data['product_id']]);
    if (!$stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Product not found in this branch"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE branch_stock SET low_stock_threshold = :t WHERE branch_id = :bid AND product_id = :pid");
    $stmt->execute([
        ':t' => $data['low_stock_threshold'],
        ':bid' => $data['branch_id'],
        ':pid' => $data['product_id']
    ]);

    echo json_encode(["success" => true, "message" => "Threshold updated successfully"]);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>