<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/audit.php';

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$user_id = (int)($data['user_id'] ?? 0);
$active = !empty($data['active']) ? 1 : 0;

try {
    $stmt = $conn->prepare("SELECT name, email, role, is_active FROM users WHERE user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit;
    }
    if (!$active && $user_id === (int)$_SESSION['user_id']) {
        echo json_encode(["success" => false, "message" => "You cannot disable your own account"]);
        exit;
    }
    if (!$active && $user['role'] === 'Admin') {
        $active_admins = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'Admin' AND is_active = 1")->fetchColumn();
        if ($active_admins <= 1) {
            echo json_encode(["success" => false, "message" => "There must be at least one active admin"]);
            exit;
        }
    }

    $conn->prepare("UPDATE users SET is_active = :a WHERE user_id = :uid")->execute([':a' => $active, ':uid' => $user_id]);
    audit_log($conn, $active ? 'USER_ENABLE' : 'USER_DISABLE', 'user', $user_id,
        ($active ? "Enabled" : "Disabled") . " {$user['role']} account {$user['name']} ({$user['email']})");

    echo json_encode(["success" => true, "message" => $user['name'] . ($active ? " can log in again" : " can no longer log in")]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
