<?php
require_once '../config/database.php';
require_once '../config/shop.php';

echo json_encode([
    "success" => true,
    "data" => [
        "free_delivery_threshold" => FREE_DELIVERY_THRESHOLD,
        "delivery_base_fee" => DELIVERY_BASE_FEE,
        "delivery_base_km" => DELIVERY_BASE_KM,
        "delivery_per_km" => DELIVERY_PER_KM,
        "delivery_max_km" => DELIVERY_MAX_KM,
        "sst_rate" => SST_RATE,
        "same_day_cutoff" => SAME_DAY_CUTOFF,
        "states" => MALAYSIA_STATES,
        "whatsapp_number" => WHATSAPP_NUMBER
    ]
]);
?>
