<?php
require_once '../config/database.php';

$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : null;
$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
if ($days < 1) $days = 1;
if ($days > 90) $days = 90;

try {
    $branch_filter = $branch_id ? "AND branch_id = :bid" : "";
    $stmt = $conn->prepare("
        SELECT DATE(order_date) AS d, COUNT(*) AS orders, COALESCE(SUM(total_amount), 0) AS total
        FROM orders
        WHERE payment_status = 'Paid' AND status != 'Cancelled'
        AND order_date >= DATE_SUB(CURDATE(), INTERVAL " . ($days - 1) . " DAY)
        $branch_filter
        GROUP BY DATE(order_date)
    ");
    if ($branch_id) $stmt->bindValue(':bid', $branch_id);
    $stmt->execute();

    $rows = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $rows[$r['d']] = $r;
    }

    $today = $conn->query("SELECT CURDATE()")->fetchColumn();
    $daily = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = new DateTime($today);
        $d->modify("-$i day");
        $key = $d->format('Y-m-d');
        $daily[] = [
            'date' => $key,
            'orders' => isset($rows[$key]) ? (int)$rows[$key]['orders'] : 0,
            'total' => isset($rows[$key]) ? (float)$rows[$key]['total'] : 0
        ];
    }

    $stmt = $conn->prepare("
        SELECT COALESCE(b.branch_name, 'Unassigned') AS branch_name,
               COUNT(*) AS orders,
               COALESCE(SUM(o.total_amount), 0) AS revenue
        FROM orders o
        LEFT JOIN branches b ON o.branch_id = b.branch_id
        WHERE o.payment_status = 'Paid' AND o.status != 'Cancelled'
        GROUP BY o.branch_id, b.branch_name
        ORDER BY revenue DESC
    ");
    $stmt->execute();
    $by_branch = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stock_filter = $branch_id ? "AND bs.branch_id = :bid" : "";
    $stmt = $conn->prepare("
        SELECT b.branch_name,
               COUNT(*) AS products,
               COALESCE(SUM(bs.stock), 0) AS total_units,
               COALESCE(SUM(bs.stock <= bs.low_stock_threshold), 0) AS low_stock_items
        FROM branch_stock bs
        JOIN branches b ON bs.branch_id = b.branch_id
        JOIN products p ON bs.product_id = p.product_id
        WHERE p.is_active = 1
        $stock_filter
        GROUP BY b.branch_id, b.branch_name
        ORDER BY b.branch_id
    ");
    if ($branch_id) $stmt->bindValue(':bid', $branch_id);
    $stmt->execute();
    $stock_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => [
            "daily_sales" => $daily,
            "sales_by_branch" => $by_branch,
            "stock_summary" => $stock_summary
        ]
    ]);
} catch(Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>