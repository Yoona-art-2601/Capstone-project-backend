<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/audit.php';
require_once '../config/warehouse.php';

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$product_id = (int)($data['product_id'] ?? 0);
$branch_id = (int)($data['branch_id'] ?? 0);
$quantity = (int)($data['quantity'] ?? 0);
$expected = parse_local_datetime($data['expected_arrival'] ?? '', '+1 day');
$note = trim((string)($data['note'] ?? ''));

if ($quantity <= 0) {
    echo json_encode(["success" => false, "message" => "Quantity must be a positive whole number"]);
    exit;
}
if ($expected === null) {
    echo json_encode(["success" => false, "message" => "Invalid expected arrival"]);
    exit;
}

try {
    $name = product_exists($conn, $product_id);
    $stmt = $conn->prepare("SELECT branch_name FROM branches WHERE branch_id = :bid");
    $stmt->execute([':bid' => $branch_id]);
    $branch = $stmt->fetchColumn();
    if (!$name || !$branch) {
        echo json_encode(["success" => false, "message" => "Product or branch not found"]);
        exit;
    }

    $now = parse_local_datetime('');
    if ($expected < $now) {
        echo json_encode(["success" => false, "message" => "Expected arrival cannot be in the past"]);
        exit;
    }

    $conn->beginTransaction();
    $upd = $conn->prepare("UPDATE warehouse_stock SET stock = stock - :qty WHERE warehouse_id = :wid AND product_id = :pid AND stock >= :qty2");
    $upd->execute([':qty' => $quantity, ':qty2' => $quantity, ':wid' => MAIN_WAREHOUSE_ID, ':pid' => $product_id]);
    if ($upd->rowCount() === 0) throw new Exception("Not enough $name in the warehouse");

    $conn->prepare("INSERT INTO warehouse_dispatches (warehouse_id, branch_id, product_id, quantity, status, dispatched_at, expected_arrival, dispatched_by, note)
                    VALUES (:wid, :bid, :pid, :qty, 'In Transit', :at, :exp, :by, :note)")
         ->execute([
             ':wid' => MAIN_WAREHOUSE_ID, ':bid' => $branch_id, ':pid' => $product_id, ':qty' => $quantity,
             ':at' => $now, ':exp' => $expected, ':by' => $_SESSION['name'] ?? 'Admin', ':note' => $note === '' ? null : $note
         ]);
    $dispatch_id = $conn->lastInsertId();
    audit_log($conn, 'WAREHOUSE_DISPATCH', 'product', $product_id,
        "Shipment #$dispatch_id: $quantity x $name from the central warehouse to $branch, expected $expected");
    $conn->commit();

    echo json_encode(["success" => true, "message" => "Shipment created and on its way to $branch", "dispatch_id" => $dispatch_id]);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
