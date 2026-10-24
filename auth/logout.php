<?php

require_once __DIR__ . '/../config/session.php';

require_once '../config/database.php';
require_once '../config/audit.php';


if (isset($_SESSION['user_id'])) {
    audit_log($conn, 'LOGOUT', 'user', $_SESSION['user_id'], "{$_SESSION['role']} logged out");
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

echo json_encode([
    "success" => true,
    "message" => "Logged out"
]);

?>
