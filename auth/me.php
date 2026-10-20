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

$stmt = $conn->prepare("SELECT phone, street, city, state, postcode FROM users WHERE user_id = :uid");
$stmt->execute([':uid' => $_SESSION['user_id']]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

echo json_encode([
    "success" => true,
    "user" => [
        "user_id" => $_SESSION['user_id'],
        "name" => $_SESSION['name'],
        "email" => $_SESSION['email'],
        "role" => $_SESSION['role'],
        "phone" => $profile['phone'] ?? null,
        "street" => $profile['street'] ?? null,
        "city" => $profile['city'] ?? null,
        "state" => $profile['state'] ?? null,
        "postcode" => $profile['postcode'] ?? null
    ]
]);

?>
