<?php
require_once '../config/database.php';
require_once '../config/audit.php';
require_once '../config/security.php';

function find_reset($conn, $token) {
    if (!preg_match('/^[0-9a-f]{64}$/', $token)) return null;
    $stmt = $conn->prepare("SELECT r.reset_id, r.user_id, u.name, u.email, u.role FROM password_resets r
                            JOIN users u ON u.user_id = r.user_id
                            WHERE r.token_hash = :hash AND r.used_at IS NULL AND r.expires_at > NOW() AND u.is_active = 1");
    $stmt->execute([':hash' => hash('sha256', $token)]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$expired = "This reset link is invalid, already used or expired. Please ask for a new one.";

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $reset = find_reset($conn, (string)($_GET['token'] ?? ''));
        echo json_encode($reset
            ? ["success" => true, "email" => $reset['email'], "role" => $reset['role']]
            : ["success" => false, "message" => $expired]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true) ?? [];
    $password = (string)($data['new_password'] ?? '');
    if ($problem = password_problem($password)) {
        echo json_encode(["success" => false, "message" => $problem]);
        exit;
    }

    $conn->beginTransaction();
    $reset = find_reset($conn, (string)($data['token'] ?? ''));
    if (!$reset) {
        $conn->rollBack();
        echo json_encode(["success" => false, "message" => $expired]);
        exit;
    }

    $conn->prepare("UPDATE users SET password = :pw WHERE user_id = :uid")
         ->execute([':pw' => password_hash($password, PASSWORD_DEFAULT), ':uid' => $reset['user_id']]);
    $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = :uid AND used_at IS NULL")
         ->execute([':uid' => $reset['user_id']]);
    clear_failed_attempts($conn, 'login', $reset['email']);
    clear_failed_attempts($conn, 'reset', $reset['email']);
    audit_log($conn, 'PASSWORD_RESET', 'user', $reset['user_id'], "Password reset with an email link for {$reset['email']}", $reset);
    $conn->commit();

    echo json_encode(["success" => true, "message" => "Your password has been changed. You can log in with the new password now.", "role" => $reset['role']]);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
