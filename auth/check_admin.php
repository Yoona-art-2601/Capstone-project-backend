<?php

session_start();

// check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Please login first"
    ]);
    exit;
}

// check user role
if ($_SESSION['role'] !== 'Admin') {
    echo json_encode([
        "success" => false,
        "message" => "Access denied. Admin only"
    ]);
    exit;
}

?>
