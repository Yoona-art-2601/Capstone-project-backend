<?php
require_once '../config/database.php';

try {
    $stmt = $conn->prepare("SELECT branch_id, branch_name, address, phone, opening_hours FROM branches ORDER BY branch_id");
    $stmt->execute();
    echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>