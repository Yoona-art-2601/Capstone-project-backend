<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['product_id']) || !isset($data['name']) || !isset($data['price'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields (product_id, name, price)"]);
    exit;
}

if (!is_numeric($data['price']) || $data['price'] < 0) {
    echo json_encode(["success" => false, "message" => "Price must be a non-negative number"]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT product_id FROM products WHERE product_id = :pid AND is_active = 1");
    $stmt->execute([':pid' => $data['product_id']]);
    if (!$stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Product not found"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE products SET name = :name, category = :category, price = :price WHERE product_id = :pid");
    $stmt->execute([
        ':name' => $data['name'],
        ':category' => $data['category'] ?? null,
        ':price' => $data['price'],
        ':pid' => $data['product_id']
    ]);

    echo json_encode(["success" => true, "message" => "Product updated successfully"]);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>