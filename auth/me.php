<?php

require_once __DIR__ . '/../config/session.php';

require_once '../config/database.php';
require_once '../config/roles.php';

$user = refresh_session_user($conn);
if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "Not logged in"
    ]);
    exit;
}

$stmt = $conn->prepare("SELECT phone, street, city, state, postcode FROM users WHERE user_id = :uid");
$stmt->execute([':uid' => $user['user_id']]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

echo json_encode([
    "success" => true,
    "user" => [
        "user_id" => (int)$user['user_id'],
        "name" => $user['name'],
        "email" => $user['email'],
        "role" => $user['role'],
        "branch_id" => $user['branch_id'] === null ? null : (int)$user['branch_id'],
        "branch_name" => $user['branch_name'],
        "phone" => $profile['phone'] ?? null,
        "street" => $profile['street'] ?? null,
        "city" => $profile['city'] ?? null,
        "state" => $profile['state'] ?? null,
        "postcode" => $profile['postcode'] ?? null
    ]
]);

?>
