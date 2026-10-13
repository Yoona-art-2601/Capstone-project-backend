<?php

session_start();

require_once '../config/database.php';

// check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Not logged in"
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "user" => [
        "user_id" => $_SESSION['user_id'],
        "name" => $_SESSION['name'],
        "email" => $_SESSION['email'],
        "role" => $_SESSION['role']
    ]
]);

?>
