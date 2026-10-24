<?php
require_once __DIR__ . '/../config/session.php';
require_once '../config/database.php';
require_once '../config/notifications.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Please login first"]);
    exit;
}

[$where, $params] = notification_scope($_GET['scope'] ?? 'customer');

try {
    $stmt = $conn->prepare("SELECT notification_id, type, title, message, link, is_read, created_at FROM notifications WHERE $where ORDER BY created_at DESC, notification_id DESC LIMIT 30");
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE $where AND is_read = 0");
    $stmt->execute($params);

    echo json_encode(["success" => true, "unread" => (int)$stmt->fetchColumn(), "data" => $items]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
