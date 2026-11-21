<?php
require_once '../config/database.php';
require_once '../config/audit.php';
require_once '../config/security.php';
require_once '../config/mail.php';

define('RESET_MINUTES', 30);

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$email = strtolower(trim((string)($data['email'] ?? '')));
$done = "If an account exists for $email, we have sent a link to reset the password. It works once and expires in " . RESET_MINUTES . " minutes.";

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Please enter a valid email address"]);
    exit;
}

try {
    $wait = attempt_lock_minutes($conn, 'reset', $email);
    if ($wait > 0) {
        http_response_code(429);
        echo json_encode(["success" => false, "message" => "Too many reset requests. Please try again in $wait minute" . ($wait === 1 ? '' : 's') . "."]);
        exit;
    }
    record_failed_attempt($conn, 'reset', $email);

    $stmt = $conn->prepare("SELECT user_id, name, email, role FROM users WHERE email = :email AND is_active = 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = :uid AND used_at IS NULL")->execute([':uid' => $user['user_id']]);
        $conn->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at, requested_ip) VALUES (:uid, :hash, NOW() + INTERVAL " . RESET_MINUTES . " MINUTE, :ip)")
             ->execute([':uid' => $user['user_id'], ':hash' => hash('sha256', $token), ':ip' => client_ip()]);

        $link = APP_URL . '/reset-password?token=' . $token;
        $name = htmlspecialchars($user['name']);
        send_mail($user['email'], 'Reset your Beacon Mart password',
            "<h2 style=\"color:#1d3a2a;margin-top:0\">Reset your password</h2>"
            . "<p>Hi $name,</p>"
            . "<p>We received a request to reset the password of your Beacon Mart account. Click the button below to choose a new password. The link works once and expires in " . RESET_MINUTES . " minutes.</p>"
            . "<p><a href=\"$link\" style=\"display:inline-block;background:#2d6a45;color:#fff;padding:12px 20px;border-radius:999px;text-decoration:none;font-weight:bold\">Choose a new password</a></p>"
            . "<p style=\"font-size:12px;color:#666\">Or open this link: <br>$link</p>"
            . "<p style=\"font-size:12px;color:#666\">If you did not ask for this, you can ignore this email. Your password stays the same.</p>");
        audit_log($conn, 'PASSWORD_RESET_REQUEST', 'user', $user['user_id'], "Password reset link sent to {$user['email']}", $user);
    }

    echo json_encode(["success" => true, "message" => $done]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
