<?php
require_once '../config/database.php';

try {
    // 今日销售额 + 订单数
    $stmt = $conn->prepare("
        SELECT COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as total_sales
        FROM orders
        WHERE DATE(order_date) = CURDATE()
        AND status != 'Cancelled'
    ");
    $stmt->execute();
    $today = $stmt->fetch(PDO::FETCH_ASSOC);

    // 本周销售额 + 订单数
    $stmt = $conn->prepare("
        SELECT COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as total_sales
        FROM orders
        WHERE YEARWEEK(order_date, 1) = YEARWEEK(CURDATE(), 1)
        AND status != 'Cancelled'
    ");
    $stmt->execute();
    $this_week = $stmt->fetch(PDO::FETCH_ASSOC);

    // 本月销售额 + 订单数
    $stmt = $conn->prepare("
        SELECT COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as total_sales
        FROM orders
        WHERE YEAR(order_date) = YEAR(CURDATE())
        AND MONTH(order_date) = MONTH(CURDATE())
        AND status != 'Cancelled'
    ");
    $stmt->execute();
    $this_month = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => [
            "today" => $today,
            "this_week" => $this_week,
            "this_month" => $this_month
        ]
    ]);
} catch(PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>