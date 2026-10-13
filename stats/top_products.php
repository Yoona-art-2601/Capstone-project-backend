<?php
require_once '../config/database.php';

$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : null;
$branch_filter = $branch_id ? "AND o.branch_id = :branch_id" : "";

try {
    $stmt = $conn->prepare("
        SELECT 
            p.product_id,
            p.name,
            p.category,
            SUM(oi.quantity) as total_sold,
            SUM(oi.subtotal) as total_revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        JOIN orders o ON oi.order_id = o.order_id
        WHERE o.payment_status = 'Paid' AND o.status != 'Cancelled'
        $branch_filter
        GROUP BY p.product_id, p.name, p.category
        ORDER BY total_sold DESC
        LIMIT 10
    ");
    if ($branch_id) $stmt->bindValue(':branch_id', $branch_id);
    $stmt->execute();
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "branch_id" => $branch_id ?? "all",
        "data" => $top_products
    ]);
} catch(PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>