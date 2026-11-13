<?php
require_once '../config/database.php';
require_once '../auth/check_staff.php';
require_once '../config/payments.php';

$per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$branch_id = staff_branch() ?? (int)($_GET['branch_id'] ?? 0);
$valid_date = fn($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && DateTime::createFromFormat('Y-m-d', $d);

$where = [];
$params = [];
if ($branch_id) {
    $where[] = "o.branch_id = :bid";
    $params[':bid'] = $branch_id;
}
$search = trim((string)($_GET['search'] ?? ''));
if ($search !== '') {
    $id = ltrim($search, '#');
    $where[] = ctype_digit($id)
        ? "(o.order_id = :oid OR o.customer_phone LIKE :s1)"
        : "(o.customer_name LIKE :s1 OR o.customer_phone LIKE :s2 OR o.customer_address LIKE :s3)";
    if (ctype_digit($id)) {
        $params[':oid'] = (int)$id;
        $params[':s1'] = '%' . $search . '%';
    } else {
        $params[':s1'] = $params[':s2'] = $params[':s3'] = '%' . $search . '%';
    }
}
if ($valid_date($_GET['date_from'] ?? null)) {
    $where[] = "o.order_date >= :from";
    $params[':from'] = $_GET['date_from'] . ' 00:00:00';
}
if ($valid_date($_GET['date_to'] ?? null)) {
    $where[] = "o.order_date <= :to";
    $params[':to'] = $_GET['date_to'] . ' 23:59:59';
}
if (in_array($_GET['delivery_type'] ?? '', ['Delivery', 'Pickup'], true)) {
    $where[] = "o.delivery_type = :dtype";
    $params[':dtype'] = $_GET['delivery_type'];
}
$base_where = $where;
$base_params = $params;

$status = $_GET['status'] ?? '';
if ($status === 'Open') {
    $where[] = "o.status NOT IN ('Completed', 'Cancelled', 'Awaiting Payment')";
} elseif ($status !== '' && $status !== 'All') {
    $where[] = "o.status = :status";
    $params[':status'] = $status;
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";
$base_sql = $base_where ? "WHERE " . implode(" AND ", $base_where) : "";

try {
    expire_stale_payments($conn);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders o $where_sql");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT o.status, COUNT(*) AS total FROM orders o $base_sql GROUP BY o.status");
    $stmt->execute($base_params);
    $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stmt = $conn->prepare("
        SELECT
            o.order_id, o.customer_name, o.customer_phone, o.customer_address, o.delivery_type, o.payment_method, o.distance_km, o.slot_date, o.slot_time, o.order_date,
            o.status, o.payment_status, o.total_amount, o.delivery_fee, o.sst_amount,
            o.branch_id, b.branch_name
        FROM orders o
        LEFT JOIN branches b ON o.branch_id = b.branch_id
        $where_sql
        ORDER BY o.order_date DESC, o.order_id DESC
        LIMIT $per_page OFFSET " . (($page - 1) * $per_page)
    );
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = $conn->prepare("
        SELECT oi.product_id, p.name, oi.quantity, oi.subtotal
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = :oid
    ");
    foreach ($orders as &$order) {
        $items->execute([':oid' => $order['order_id']]);
        $order['items'] = $items->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($order);
    attach_history($conn, $orders);

    echo json_encode([
        "success" => true,
        "data" => $orders,
        "page" => $page,
        "pages" => max(1, (int)ceil($total / $per_page)),
        "total" => $total,
        "counts" => (object)$counts
    ]);
} catch(PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => safe_error($e)
    ]);
}
?>
