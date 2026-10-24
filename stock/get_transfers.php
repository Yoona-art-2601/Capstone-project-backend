<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';

$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : null;
$filter = $branch_id ? "WHERE t.from_branch_id = :bid1 OR t.to_branch_id = :bid2" : "";

try {
    $stmt = $conn->prepare("
        SELECT
            t.transfer_id,
            t.product_id,
            p.name,
            t.from_branch_id,
            fb.branch_name AS from_branch_name,
            t.to_branch_id,
            tb.branch_name AS to_branch_name,
            t.quantity,
            t.transfer_date
        FROM stock_transfers t
        JOIN products p ON t.product_id = p.product_id
        JOIN branches fb ON t.from_branch_id = fb.branch_id
        JOIN branches tb ON t.to_branch_id = tb.branch_id
        $filter
        ORDER BY t.transfer_date DESC, t.transfer_id DESC
        LIMIT 200
    ");
    if ($branch_id) {
        $stmt->bindValue(':bid1', $branch_id);
        $stmt->bindValue(':bid2', $branch_id);
    }
    $stmt->execute();

    echo json_encode([
        "success" => true,
        "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>