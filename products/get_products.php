<?php
require_once '../config/database.php';

// 从网址参数拿 branch_id，例如：get_products.php?branch_id=1
$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : 1; // 默认分店1

try {
    $stmt = $conn->prepare("
        SELECT 
            p.product_id,
            p.name,
            p.category,
            p.price,
            bs.stock,
            bs.low_stock_threshold,
            p.created_at
        FROM products p
        JOIN branch_stock bs ON p.product_id = bs.product_id
        WHERE bs.branch_id = :branch_id
        ORDER BY p.product_id DESC
    ");
    $stmt->execute([':branch_id' => $branch_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $products
    ]);
} catch(PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>