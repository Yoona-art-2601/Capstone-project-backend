<?php
require_once '../config/database.php';

$product_id = (int)($_GET['product_id'] ?? 0);

try {
    $stmt = $conn->prepare("SELECT COUNT(*) AS review_count, ROUND(AVG(rating), 1) AS avg_rating FROM product_reviews WHERE product_id = :pid");
    $stmt->execute([':pid' => $product_id]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("
        SELECT r.rating, r.comment, r.created_at, u.name
        FROM product_reviews r
        LEFT JOIN users u ON u.user_id = r.user_id
        WHERE r.product_id = :pid
        ORDER BY r.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([':pid' => $product_id]);
    $reviews = array_map(function ($r) {
        $first = trim(explode(' ', (string)$r['name'])[0]);
        $r['reviewer'] = $first !== '' ? $first : 'Customer';
        unset($r['name']);
        $r['rating'] = (int)$r['rating'];
        return $r;
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    echo json_encode([
        "success" => true,
        "data" => [
            "review_count" => (int)$summary['review_count'],
            "avg_rating" => $summary['avg_rating'] !== null ? (float)$summary['avg_rating'] : null,
            "reviews" => $reviews
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
