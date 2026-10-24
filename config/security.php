<?php
define('ATTEMPT_WINDOW_MINUTES', 15);
define('ATTEMPT_LIMITS', [
    'login' => ['email' => 5, 'ip' => 20],
    'admin_code' => ['email' => null, 'ip' => 5],
    'password' => ['email' => 5, 'ip' => 20]
]);
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_MAX_LENGTH', 72);

function client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function attempt_lock_minutes($conn, $kind, $email = null) {
    $conn->prepare("DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY")->execute();
    $limits = ATTEMPT_LIMITS[$kind];
    $checks = [['ip_address', client_ip(), $limits['ip']]];
    if ($email !== null && $limits['email'] !== null) $checks[] = ['email', strtolower($email), $limits['email']];

    $wait = 0;
    foreach ($checks as [$column, $value, $limit]) {
        $stmt = $conn->prepare("SELECT COUNT(*), MIN(attempted_at) FROM login_attempts
                                WHERE kind = :kind AND $column = :value AND attempted_at > NOW() - INTERVAL " . ATTEMPT_WINDOW_MINUTES . " MINUTE");
        $stmt->execute([':kind' => $kind, ':value' => $value]);
        [$count, $oldest] = $stmt->fetch(PDO::FETCH_NUM);
        if ((int)$count >= $limit) {
            $stmt = $conn->prepare("SELECT CEIL(TIMESTAMPDIFF(SECOND, NOW(), :oldest + INTERVAL " . ATTEMPT_WINDOW_MINUTES . " MINUTE) / 60)");
            $stmt->execute([':oldest' => $oldest]);
            $wait = max($wait, (int)$stmt->fetchColumn(), 1);
        }
    }
    return $wait;
}

function record_failed_attempt($conn, $kind, $email = null) {
    $conn->prepare("INSERT INTO login_attempts (kind, email, ip_address) VALUES (:kind, :email, :ip)")
         ->execute([':kind' => $kind, ':email' => $email === null ? null : strtolower($email), ':ip' => client_ip()]);
}

function clear_failed_attempts($conn, $kind, $email) {
    $conn->prepare("DELETE FROM login_attempts WHERE kind = :kind AND email = :email")
         ->execute([':kind' => $kind, ':email' => strtolower($email)]);
}

function locked_message($minutes) {
    return "Too many failed attempts. Please try again in $minutes minute" . ($minutes === 1 ? '' : 's') . ".";
}

function password_problem($password) {
    if (strlen($password) < PASSWORD_MIN_LENGTH) return "Password must be at least " . PASSWORD_MIN_LENGTH . " characters";
    if (strlen($password) > PASSWORD_MAX_LENGTH) return "Password must be " . PASSWORD_MAX_LENGTH . " characters or fewer";
    return null;
}
?>
