<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['name']) || !isset($data['price'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields (name, price)"
    ]);
    exit;
}

try {
    $conn->beginTransaction();

    // add product to products table
    $stmt = $conn->prepare("INSERT INTO products (name, category, price) 
                            VALUES (:name, :category, :price)");
    $stmt->execute([
        ':name' => $data['name'],
        ':category' => $data['category'] ?? null,
        ':price' => $data['price']
    ]);
    $product_id = $conn->lastInsertId();

    // take all branches and insert initial stock for each branch
    $stmt = $conn->prepare("SELECT branch_id FROM branches");
    $stmt->execute();
    $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $initial_stock = $data['initial_stock'] ?? 0;
    $threshold = $data['low_stock_threshold'] ?? 10;

    foreach ($branches as $branch) {
        $stmt = $conn->prepare("INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold) 
                                VALUES (:bid, :pid, :stock, :threshold)");
        $stmt->execute([
            ':bid' => $branch['branch_id'],
            ':pid' => $product_id,
            ':stock' => $initial_stock,
            ':threshold' => $threshold
        ]);
    }

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Product added successfully to all branches",
        "product_id" => $product_id
    ]);
} catch(PDOException $e) {
    $conn->rollBack();
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>