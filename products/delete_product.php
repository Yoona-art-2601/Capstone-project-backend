<?php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['product_id'])) {
    echo json_encode(["success" => false, "message" => "Missing product_id"]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT product_id FROM products WHERE product_id = :pid AND is_active = 1");
    $stmt->execute([':pid' => $data['product_id']]);
    if (!$stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Product not found"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE products SET is_active = 0 WHERE product_id = :pid");
    $stmt->execute([':pid' => $data['product_id']]);

    echo json_encode(["success" => true, "message" => "Product removed from store"]);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>