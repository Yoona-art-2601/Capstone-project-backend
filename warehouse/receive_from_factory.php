<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/audit.php';
require_once '../config/warehouse.php';

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$product_id = (int)($data['product_id'] ?? 0);
$quantity = (int)($data['quantity'] ?? 0);
$supplier = trim((string)($data['supplier'] ?? ''));
$reference = trim((string)($data['reference'] ?? ''));
$received_at = parse_local_datetime($data['received_at'] ?? '');

if ($quantity <= 0) {
    echo json_encode(["success" => false, "message" => "Quantity must be a positive whole number"]);
    exit;
}
if ($supplier === '') {
    echo json_encode(["success" => false, "message" => "Please enter the factory or supplier"]);
    exit;
}
if ($received_at === null) {
    echo json_encode(["success" => false, "message" => "Invalid arrival date and time"]);
    exit;
}

try {
    $name = product_exists($conn, $product_id);
    if (!$name) {
        echo json_encode(["success" => false, "message" => "Product not found"]);
        exit;
    }

    $conn->beginTransaction();
    $conn->prepare("INSERT INTO warehouse_stock (warehouse_id, product_id, stock) VALUES (:wid, :pid, :qty)
                    ON DUPLICATE KEY UPDATE stock = stock + VALUES(stock)")
         ->execute([':wid' => MAIN_WAREHOUSE_ID, ':pid' => $product_id, ':qty' => $quantity]);
    $conn->prepare("INSERT INTO factory_receipts (warehouse_id, product_id, quantity, supplier, reference, received_at, received_by)
                    VALUES (:wid, :pid, :qty, :supplier, :ref, :at, :by)")
         ->execute([
             ':wid' => MAIN_WAREHOUSE_ID, ':pid' => $product_id, ':qty' => $quantity, ':supplier' => $supplier,
             ':ref' => $reference === '' ? null : $reference, ':at' => $received_at, ':by' => $_SESSION['name'] ?? 'Admin'
         ]);
    audit_log($conn, 'FACTORY_RECEIPT', 'product', $product_id,
        "Received $quantity x $name from $supplier into the central warehouse" . ($reference ? " (ref $reference)" : '') . " at $received_at");
    $conn->commit();

    echo json_encode(["success" => true, "message" => "Stock received into the warehouse"]);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
