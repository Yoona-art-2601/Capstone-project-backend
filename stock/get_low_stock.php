<?php
require_once '../config/database.php';

$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : null;
$branch_filter = $branch_id ? "AND bs.branch_id = :branch_id" : "";

try {
    $stmt = $conn->prepare("
        SELECT
            bs.branch_id,
            b.branch_name,
            p.product_id,
            p.name,
            bs.stock,
            bs.low_stock_threshold
        FROM branch_stock bs
        JOIN products p ON bs.product_id = p.product_id
        JOIN branches b ON bs.branch_id = b.branch_id
        WHERE bs.stock <= bs.low_stock_threshold AND p.is_active = 1
        $branch_filter
        ORDER BY bs.stock ASC
    ");
    if ($branch_id) $stmt->bindValue(':branch_id', $branch_id);
    $stmt->execute();

    echo json_encode([
        "success" => true,
        "branch_id" => $branch_id ?? "all",
        "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch(PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>