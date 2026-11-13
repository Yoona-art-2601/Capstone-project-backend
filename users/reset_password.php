<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/audit.php';
require_once '../config/security.php';

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$user_id = (int)($data['user_id'] ?? 0);
$password = (string)($data['new_password'] ?? '');

if ($problem = password_problem($password)) {
    echo json_encode(["success" => false, "message" => $problem]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT name, email, role FROM users WHERE user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit;
    }

    $conn->prepare("UPDATE users SET password = :pw WHERE user_id = :uid")
         ->execute([':pw' => password_hash($password, PASSWORD_DEFAULT), ':uid' => $user_id]);
    clear_failed_attempts($conn, 'login', $user['email']);
    audit_log($conn, 'USER_PASSWORD_RESET', 'user', $user_id, "Reset the password of {$user['role']} {$user['name']} ({$user['email']})");

    echo json_encode(["success" => true, "message" => "New password set for {$user['name']}. Ask them to change it on My Account after logging in"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
