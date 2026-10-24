<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Please login first"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$order_id = (int)($data['order_id'] ?? 0);
$product_id = (int)($data['product_id'] ?? 0);
$rating = (int)($data['rating'] ?? 0);
$comment = trim((string)($data['comment'] ?? ''));

if ($rating < 1 || $rating > 5) {
    echo json_encode(["success" => false, "message" => "Please choose a rating from 1 to 5 stars"]);
    exit;
}
if (mb_strlen($comment) > 1000) {
    echo json_encode(["success" => false, "message" => "Comment must be 1000 characters or fewer"]);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT o.status
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.order_id
        WHERE o.order_id = :oid AND o.user_id = :uid AND oi.product_id = :pid
    ");
    $stmt->execute([':oid' => $order_id, ':uid' => $_SESSION['user_id'], ':pid' => $product_id]);
    $status = $stmt->fetchColumn();

    if ($status === false) {
        echo json_encode(["success" => false, "message" => "You can only review products from your own orders"]);
        exit;
    }
    if ($status !== 'Completed') {
        echo json_encode(["success" => false, "message" => "You can review this product once the order is completed"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT review_id FROM product_reviews WHERE order_id = :oid AND product_id = :pid");
    $stmt->execute([':oid' => $order_id, ':pid' => $product_id]);
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "You have already reviewed this product for this order"]);
        exit;
    }

    $conn->prepare("INSERT INTO product_reviews (product_id, user_id, order_id, rating, comment) VALUES (:pid, :uid, :oid, :rating, :comment)")
         ->execute([
             ':pid' => $product_id,
             ':uid' => $_SESSION['user_id'],
             ':oid' => $order_id,
             ':rating' => $rating,
             ':comment' => $comment === '' ? null : $comment
         ]);

    echo json_encode(["success" => true, "message" => "Thank you for your review"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
