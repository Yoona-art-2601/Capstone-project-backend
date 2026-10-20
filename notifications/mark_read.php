<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Please login first"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$is_admin = ($_SESSION['role'] ?? '') === 'Admin';
$where = $is_admin ? "audience = 'Admin'" : "audience = 'Customer' AND user_id = :uid";
$params = $is_admin ? [] : [':uid' => $_SESSION['user_id']];

try {
    if (!empty($data['all'])) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE $where AND is_read = 0");
    } else {
        $where .= " AND notification_id = :nid";
        $params[':nid'] = (int)($data['notification_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE $where");
    }
    $stmt->execute($params);
    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
