<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/audit.php';
require_once '../config/notifications.php';
require_once '../config/warehouse.php';

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$dispatch_id = (int)($data['dispatch_id'] ?? 0);
$action = $data['action'] ?? '';

if (!in_array($action, ['receive', 'cancel'], true)) {
    echo json_encode(["success" => false, "message" => "Action must be receive or cancel"]);
    exit;
}

try {
    $conn->beginTransaction();
    $stmt = $conn->prepare("
        SELECT d.*, p.name AS product_name, b.branch_name
        FROM warehouse_dispatches d
        JOIN products p ON p.product_id = d.product_id
        JOIN branches b ON b.branch_id = d.branch_id
        WHERE d.dispatch_id = :did FOR UPDATE
    ");
    $stmt->execute([':did' => $dispatch_id]);
    $d = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$d) throw new Exception("Shipment not found");
    if ($d['status'] !== 'In Transit') throw new Exception("This shipment is already {$d['status']}");

    $now = parse_local_datetime('');
    $by = $_SESSION['name'] ?? 'Admin';

    if ($action === 'receive') {
        $conn->prepare("INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold) VALUES (:bid, :pid, :qty, 10)
                        ON DUPLICATE KEY UPDATE stock = stock + VALUES(stock)")
             ->execute([':bid' => $d['branch_id'], ':pid' => $d['product_id'], ':qty' => $d['quantity']]);
        $conn->prepare("INSERT INTO stock_logs (product_id, branch_id, change_type, quantity_change) VALUES (:pid, :bid, 'Warehouse In', :qty)")
             ->execute([':pid' => $d['product_id'], ':bid' => $d['branch_id'], ':qty' => $d['quantity']]);
        $conn->prepare("UPDATE warehouse_dispatches SET status = 'Received', received_at = :at, received_by = :by WHERE dispatch_id = :did")
             ->execute([':at' => $now, ':by' => $by, ':did' => $dispatch_id]);
        check_stock_levels($conn, $d['branch_id'], [$d['product_id']]);
        audit_log($conn, 'WAREHOUSE_RECEIVED', 'product', $d['product_id'],
            "Shipment #$dispatch_id received at {$d['branch_name']}: +{$d['quantity']} {$d['product_name']}");
        $message = "{$d['quantity']} x {$d['product_name']} added to {$d['branch_name']}";
    } else {
        $conn->prepare("UPDATE warehouse_stock SET stock = stock + :qty WHERE warehouse_id = :wid AND product_id = :pid")
             ->execute([':qty' => $d['quantity'], ':wid' => $d['warehouse_id'], ':pid' => $d['product_id']]);
        $conn->prepare("UPDATE warehouse_dispatches SET status = 'Cancelled', received_at = :at, received_by = :by WHERE dispatch_id = :did")
             ->execute([':at' => $now, ':by' => $by, ':did' => $dispatch_id]);
        audit_log($conn, 'WAREHOUSE_DISPATCH_CANCEL', 'product', $d['product_id'],
            "Shipment #$dispatch_id to {$d['branch_name']} cancelled, {$d['quantity']} {$d['product_name']} returned to the warehouse");
        $message = "Shipment cancelled and stock returned to the warehouse";
    }

    $conn->commit();
    echo json_encode(["success" => true, "message" => $message]);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
