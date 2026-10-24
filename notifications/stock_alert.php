<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Please login first"]);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $conn->prepare("SELECT product_id, branch_id FROM stock_alerts WHERE user_id = :uid AND notified_at IS NULL");
        $stmt->execute([':uid' => $_SESSION['user_id']]);
        echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true) ?? [];
    $product_id = (int)($data['product_id'] ?? 0);
    $branch_id = (int)($data['branch_id'] ?? 0);

    $stmt = $conn->prepare("SELECT bs.stock FROM branch_stock bs JOIN products p ON p.product_id = bs.product_id WHERE bs.product_id = :pid AND bs.branch_id = :bid AND p.is_active = 1");
    $stmt->execute([':pid' => $product_id, ':bid' => $branch_id]);
    $stock = $stmt->fetchColumn();
    if ($stock === false) {
        echo json_encode(["success" => false, "message" => "Product not found at this branch"]);
        exit;
    }
    if ((int)$stock > 0) {
        echo json_encode(["success" => false, "message" => "This product is in stock now"]);
        exit;
    }

    $conn->prepare("INSERT INTO stock_alerts (user_id, product_id, branch_id) VALUES (:uid, :pid, :bid)
                    ON DUPLICATE KEY UPDATE created_at = NOW(), notified_at = NULL")
         ->execute([':uid' => $_SESSION['user_id'], ':pid' => $product_id, ':bid' => $branch_id]);

    echo json_encode(["success" => true, "message" => "We will notify you when it is back in stock"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
