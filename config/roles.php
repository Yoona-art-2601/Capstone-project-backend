<?php
define('STAFF_ROLES', ['Admin', 'Staff']);

function refresh_session_user($conn) {
    if (!isset($_SESSION['user_id'])) return null;
    $stmt = $conn->prepare("SELECT u.user_id, u.name, u.email, u.role, u.branch_id, u.is_active, b.branch_name
                            FROM users u LEFT JOIN branches b ON b.branch_id = u.branch_id WHERE u.user_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !(int)$user['is_active']) {
        $_SESSION = [];
        return null;
    }
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['branch_id'] = $user['branch_id'] === null ? null : (int)$user['branch_id'];
    return $user;
}

function require_roles($conn, $roles, $denied = "Access denied") {
    $user = refresh_session_user($conn);
    if (!$user) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Please login first"]);
        exit;
    }
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => $denied]);
        exit;
    }
    if ($user['role'] === 'Staff' && $user['branch_id'] === null) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Your staff account has no branch yet. Please ask an admin"]);
        exit;
    }
    return $user;
}

function staff_branch() {
    return ($_SESSION['role'] ?? '') === 'Staff' ? (int)$_SESSION['branch_id'] : null;
}

function deny_other_branch($branch_id) {
    $own = staff_branch();
    if ($own !== null && (int)$branch_id !== $own) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "You can only manage your own branch"]);
        exit;
    }
}
?>
