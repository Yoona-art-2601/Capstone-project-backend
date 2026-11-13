<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';

$per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));

$where = [];
$params = [];
$role = $_GET['role'] ?? '';
if (in_array($role, ['Admin', 'Staff', 'Customer'], true)) {
    $where[] = "u.role = :role";
    $params[':role'] = $role;
}
$status = $_GET['status'] ?? '';
if ($status === 'active' || $status === 'disabled') {
    $where[] = "u.is_active = " . ($status === 'active' ? 1 : 0);
}
$search = trim((string)($_GET['search'] ?? ''));
if ($search !== '') {
    $where[] = "(u.name LIKE :s1 OR u.email LIKE :s2 OR u.phone LIKE :s3)";
    $params[':s1'] = $params[':s2'] = $params[':s3'] = '%' . $search . '%';
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users u $where_sql");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $stmt = $conn->prepare("
        SELECT u.user_id, u.name, u.email, u.phone, u.role, u.branch_id, b.branch_name, u.is_active, u.created_at,
               (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.user_id) AS orders
        FROM users u LEFT JOIN branches b ON b.branch_id = u.branch_id
        $where_sql
        ORDER BY FIELD(u.role, 'Admin', 'Staff', 'Customer'), u.name
        LIMIT $per_page OFFSET " . (($page - 1) * $per_page)
    );
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $counts = $conn->query("SELECT role, COUNT(*) AS total, SUM(is_active) AS active FROM users GROUP BY role")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $users,
        "page" => $page,
        "pages" => max(1, (int)ceil($total / $per_page)),
        "total" => $total,
        "counts" => $counts,
        "me" => (int)$_SESSION['user_id']
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
