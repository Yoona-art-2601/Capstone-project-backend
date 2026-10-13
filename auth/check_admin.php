<?php

session_start();

// check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Please login first"
    ]);
    exit;
}

// check user role
if ($_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Access denied. Admin only"
    ]);
    exit;
}

?>
