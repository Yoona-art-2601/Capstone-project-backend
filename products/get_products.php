<?php
require_once '../config/database.php';

// get the branch_id from the query parameter, default to 1 if not provided
$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : 1; // 默认分店1

try {
    $stmt = $conn->prepare("
        SELECT 
            p.product_id,
            p.name,
            p.category,
            p.price,
            p.sale_price,
            p.image_url,
            p.description,
            bs.stock,
            bs.low_stock_threshold,
            p.created_at,
            COALESCE(sold.qty, 0) AS sold,
            rv.avg_rating,
            COALESCE(rv.review_count, 0) AS review_count
        FROM products p
        JOIN branch_stock bs ON p.product_id = bs.product_id
        LEFT JOIN (
            SELECT oi.product_id, SUM(oi.quantity) AS qty
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.order_id
            WHERE o.payment_status = 'Paid' AND o.status != 'Cancelled'
            GROUP BY oi.product_id
        ) sold ON sold.product_id = p.product_id
        LEFT JOIN (
            SELECT product_id, ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS review_count
            FROM product_reviews
            GROUP BY product_id
        ) rv ON rv.product_id = p.product_id
        WHERE bs.branch_id = :branch_id AND p.is_active = 1
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