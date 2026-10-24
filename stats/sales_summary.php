<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';

$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : null;
$branch_filter = $branch_id ? "AND branch_id = :branch_id" : "";

try {
    // today
    $stmt = $conn->prepare("
        SELECT COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as total_sales
        FROM orders
        WHERE DATE(order_date) = CURDATE()
        AND status != 'Cancelled' AND payment_status = 'Paid'
        $branch_filter
    ");
    if ($branch_id) $stmt->bindValue(':branch_id', $branch_id);
    $stmt->execute();
    $today = $stmt->fetch(PDO::FETCH_ASSOC);

    // this week
    $stmt = $conn->prepare("
        SELECT COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as total_sales
        FROM orders
        WHERE YEARWEEK(order_date, 1) = YEARWEEK(CURDATE(), 1)
        AND status != 'Cancelled' AND payment_status = 'Paid'
        $branch_filter
    ");
    if ($branch_id) $stmt->bindValue(':branch_id', $branch_id);
    $stmt->execute();
    $this_week = $stmt->fetch(PDO::FETCH_ASSOC);

    // this month
    $stmt = $conn->prepare("
        SELECT COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as total_sales
        FROM orders
        WHERE YEAR(order_date) = YEAR(CURDATE())
        AND MONTH(order_date) = MONTH(CURDATE())
        AND status != 'Cancelled' AND payment_status = 'Paid'
        $branch_filter
    ");
    if ($branch_id) $stmt->bindValue(':branch_id', $branch_id);
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
        "message" => safe_error($e)
    ]);
}
?>