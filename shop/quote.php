<?php
session_start();
require_once '../config/database.php';
require_once '../config/checkout.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Please login first"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true) ?? [];

try {
    $quote = build_checkout($conn, $data, false);
    echo json_encode([
        "success" => true,
        "data" => [
            "subtotal" => $quote['subtotal'],
            "sst" => $quote['sst'],
            "delivery_fee" => $quote['delivery_fee'],
            "distance_km" => $quote['distance_km'],
            "total" => $quote['total']
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
