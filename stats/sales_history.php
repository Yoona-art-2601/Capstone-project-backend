<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';

$per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$branch_id = isset($_GET['branch_id']) && $_GET['branch_id'] !== '' && $_GET['branch_id'] !== 'all' ? (int)$_GET['branch_id'] : null;
$format = $_GET['format'] ?? 'json';
$all = !empty($_GET['all']) || $format === 'csv';

$valid_date = fn($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && DateTime::createFromFormat('Y-m-d', $d);
$tz = new DateTimeZone('Asia/Kuala_Lumpur');
$date_to = $valid_date($_GET['date_to'] ?? null) ? $_GET['date_to'] : (new DateTime('now', $tz))->format('Y-m-d');
$date_from = $valid_date($_GET['date_from'] ?? null) ? $_GET['date_from'] : (new DateTime($date_to . ' -29 days', $tz))->format('Y-m-d');
if ($date_from > $date_to) {
    [$date_from, $date_to] = [$date_to, $date_from];
}

$where = "o.payment_status = 'Paid' AND o.status != 'Cancelled' AND o.order_date BETWEEN :from AND :to";
$params = [':from' => $date_from . ' 00:00:00', ':to' => $date_to . ' 23:59:59'];
if ($branch_id) {
    $where .= " AND o.branch_id = :bid";
    $params[':bid'] = $branch_id;
}

try {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT o.order_id) AS orders, COALESCE(SUM(oi.quantity), 0) AS units, COALESCE(SUM(oi.subtotal), 0) AS revenue,
               COUNT(DISTINCT oi.product_id) AS products
        FROM orders o JOIN order_items oi ON oi.order_id = o.order_id
        WHERE $where
    ");
    $stmt->execute($params);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_revenue = (float)$totals['revenue'];

    $branch_name = 'All branches';
    if ($branch_id) {
        $b = $conn->prepare("SELECT branch_name FROM branches WHERE branch_id = :bid");
        $b->execute([':bid' => $branch_id]);
        $branch_name = $b->fetchColumn() ?: 'Unknown branch';
    }

    $limit = $all ? "" : "LIMIT $per_page OFFSET " . (($page - 1) * $per_page);
    $stmt = $conn->prepare("
        SELECT p.product_id, p.name, p.category,
               SUM(oi.quantity) AS units,
               COUNT(DISTINCT o.order_id) AS orders,
               SUM(oi.subtotal) AS revenue,
               MIN(DATE(o.order_date)) AS first_sale,
               MAX(DATE(o.order_date)) AS last_sale
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.order_id
        JOIN products p ON p.product_id = oi.product_id
        WHERE $where
        GROUP BY p.product_id, p.name, p.category
        ORDER BY revenue DESC, units DESC, p.name
        $limit
    ");
    $stmt->execute($params);
    $rows = array_map(function ($r) use ($total_revenue) {
        $r['units'] = (int)$r['units'];
        $r['orders'] = (int)$r['orders'];
        $r['revenue'] = round((float)$r['revenue'], 2);
        $r['share'] = $total_revenue > 0 ? round($r['revenue'] / $total_revenue * 100, 1) : 0;
        return $r;
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    if ($format === 'csv') {
        $filename = "beacon-mart-sales-$date_from-to-$date_to.csv";
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Access-Control-Expose-Headers: Content-Disposition');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Beacon Mart sales report']);
        fputcsv($out, ['Branch', $branch_name]);
        fputcsv($out, ['Period', "$date_from to $date_to"]);
        fputcsv($out, ['Paid orders', $totals['orders'], 'Units sold', $totals['units'], 'Revenue (RM, before SST and delivery)', number_format($total_revenue, 2, '.', '')]);
        fputcsv($out, []);
        fputcsv($out, ['Rank', 'Product', 'Category', 'Units sold', 'Orders', 'Revenue (RM)', 'Share of revenue (%)', 'First sale', 'Last sale']);
        foreach ($rows as $i => $r) {
            fputcsv($out, [$i + 1, $r['name'], $r['category'], $r['units'], $r['orders'], number_format($r['revenue'], 2, '.', ''), $r['share'], $r['first_sale'], $r['last_sale']]);
        }
        fclose($out);
        exit;
    }

    $product_count = (int)$totals['products'];
    echo json_encode([
        "success" => true,
        "branch_name" => $branch_name,
        "date_from" => $date_from,
        "date_to" => $date_to,
        "totals" => [
            "orders" => (int)$totals['orders'],
            "units" => (int)$totals['units'],
            "revenue" => round($total_revenue, 2),
            "products" => $product_count
        ],
        "page" => $all ? 1 : $page,
        "pages" => $all ? 1 : max(1, (int)ceil($product_count / $per_page)),
        "data" => $rows
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
