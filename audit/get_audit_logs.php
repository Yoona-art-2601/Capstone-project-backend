<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';

$per_page = 50;
$page = max(1, (int)($_GET['page'] ?? 1));

$where = [];
$params = [];

if (!empty($_GET['action'])) {
    $where[] = "action = :action";
    $params[':action'] = $_GET['action'];
}
if (!empty($_GET['search'])) {
    $where[] = "(details LIKE :search OR user_name LIKE :search2)";
    $params[':search'] = '%' . $_GET['search'] . '%';
    $params[':search2'] = '%' . $_GET['search'] . '%';
}
if (!empty($_GET['date_from'])) {
    $where[] = "created_at >= :date_from";
    $params[':date_from'] = $_GET['date_from'] . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $where[] = "created_at <= :date_to";
    $params[':date_to'] = $_GET['date_to'] . ' 23:59:59';
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM audit_logs $where_sql");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $offset = ($page - 1) * $per_page;
    $stmt = $conn->prepare("
        SELECT log_id, user_id, user_name, user_role, action, entity_type, entity_id, details, ip_address, created_at
        FROM audit_logs
        $where_sql
        ORDER BY created_at DESC, log_id DESC
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $actions = $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        "success" => true,
        "data" => $logs,
        "total" => $total,
        "page" => $page,
        "pages" => max(1, (int)ceil($total / $per_page)),
        "actions" => $actions
    ]);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
