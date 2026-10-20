<?php
require_once '../config/database.php';
require_once '../config/shop.php';

$branch_id = (int)($_GET['branch_id'] ?? 0);

try {
    $stmt = $conn->prepare("SELECT opening_hours FROM branches WHERE branch_id = :bid");
    $stmt->execute([':bid' => $branch_id]);
    $hours = $stmt->fetchColumn();
    if ($hours === false) {
        echo json_encode(["success" => false, "message" => "Branch not found"]);
        exit;
    }
    echo json_encode([
        "success" => true,
        "now" => kl_now()->format('Y-m-d H:i'),
        "cutoff" => SAME_DAY_CUTOFF,
        "data" => available_slots($hours)
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
