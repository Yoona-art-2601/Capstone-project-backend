<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/audit.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['product_id']) || !isset($data['name']) || !isset($data['price'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields (product_id, name, price)"]);
    exit;
}

if (!is_numeric($data['price']) || $data['price'] < 0) {
    echo json_encode(["success" => false, "message" => "Price must be a non-negative number"]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT name, category, price, sale_price FROM products WHERE product_id = :pid AND is_active = 1");
    $stmt->execute([':pid' => $data['product_id']]);
    $old = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$old) {
        echo json_encode(["success" => false, "message" => "Product not found"]);
        exit;
    }

    $sale_price = $old['sale_price'];
    if (array_key_exists('sale_price', $data)) {
        if ($data['sale_price'] === null || $data['sale_price'] === '') {
            $sale_price = null;
        } elseif (!is_numeric($data['sale_price']) || $data['sale_price'] < 0) {
            echo json_encode(["success" => false, "message" => "Sale price must be a non-negative number"]);
            exit;
        } elseif ((float)$data['sale_price'] >= (float)$data['price']) {
            echo json_encode(["success" => false, "message" => "Sale price must be lower than the normal price"]);
            exit;
        } else {
            $sale_price = $data['sale_price'];
        }
    }

    $stmt = $conn->prepare("UPDATE products SET name = :name, category = :category, price = :price, sale_price = :sale WHERE product_id = :pid");
    $stmt->execute([
        ':name' => $data['name'],
        ':category' => $data['category'] ?? null,
        ':price' => $data['price'],
        ':sale' => $sale_price,
        ':pid' => $data['product_id']
    ]);

    $fmt = fn($v) => $v === null ? 'none' : 'RM ' . number_format($v, 2);
    $changes = [];
    if ($old['name'] !== $data['name']) $changes[] = "name: {$old['name']} -> {$data['name']}";
    if (($old['category'] ?? '') !== ($data['category'] ?? '')) $changes[] = "category: {$old['category']} -> " . ($data['category'] ?? '');
    if ((float)$old['price'] !== (float)$data['price']) $changes[] = "price: RM {$old['price']} -> RM " . number_format($data['price'], 2);
    if ($old['sale_price'] === null ? $sale_price !== null : ($sale_price === null || (float)$old['sale_price'] !== (float)$sale_price)) {
        $changes[] = "sale price: " . $fmt($old['sale_price']) . " -> " . $fmt($sale_price);
    }
    audit_log($conn, 'PRODUCT_UPDATE', 'product', $data['product_id'],
        "Updated {$old['name']}: " . ($changes ? implode('; ', $changes) : 'no changes'));

    echo json_encode(["success" => true, "message" => "Product updated successfully"]);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
