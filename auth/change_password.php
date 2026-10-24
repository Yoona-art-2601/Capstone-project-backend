<?php
require_once __DIR__ . '/../config/session.php';
require_once '../config/database.php';
require_once '../config/audit.php';
require_once '../config/security.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Please login first"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$current = (string)($data['current_password'] ?? '');
$new = (string)($data['new_password'] ?? '');

if ($current === '' || $new === '') {
    echo json_encode(["success" => false, "message" => "Please enter your current and new password"]);
    exit;
}
if ($problem = password_problem($new)) {
    echo json_encode(["success" => false, "message" => $problem]);
    exit;
}
if ($new === $current) {
    echo json_encode(["success" => false, "message" => "The new password must be different from the current one"]);
    exit;
}

try {
    $email = $_SESSION['email'] ?? '';
    $wait = attempt_lock_minutes($conn, 'password', $email);
    if ($wait > 0) {
        http_response_code(429);
        echo json_encode(["success" => false, "message" => locked_message($wait)]);
        exit;
    }

    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $hash = $stmt->fetchColumn();

    if ($hash === false || !password_verify($current, $hash)) {
        record_failed_attempt($conn, 'password', $email);
        audit_log($conn, 'PASSWORD_CHANGE_FAILED', 'user', $_SESSION['user_id'], "Wrong current password when changing password");
        echo json_encode(["success" => false, "message" => "Your current password is incorrect"]);
        exit;
    }

    $conn->prepare("UPDATE users SET password = :hash WHERE user_id = :uid")
         ->execute([':hash' => password_hash($new, PASSWORD_DEFAULT), ':uid' => $_SESSION['user_id']]);
    clear_failed_attempts($conn, 'password', $email);
    session_regenerate_id(true);
    audit_log($conn, 'PASSWORD_CHANGE', 'user', $_SESSION['user_id'], "{$_SESSION['role']} changed their password");

    echo json_encode(["success" => true, "message" => "Your password has been changed"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
