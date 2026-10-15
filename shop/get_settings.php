<?php
require_once '../config/database.php';
require_once '../config/shop.php';

echo json_encode([
    "success" => true,
    "data" => [
        "delivery_fee" => DELIVERY_FEE,
        "free_delivery_threshold" => FREE_DELIVERY_THRESHOLD,
        "whatsapp_number" => WHATSAPP_NUMBER
    ]
]);
?>
