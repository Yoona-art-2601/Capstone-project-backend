<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/audit.php';

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$remove = !empty($_POST['remove']);

if ($product_id <= 0) {
    echo json_encode(["success" => false, "message" => "Missing product_id"]);
    exit;
}

$stmt = $conn->prepare("SELECT name, image_url FROM products WHERE product_id = :pid AND is_active = 1");
$stmt->execute([':pid' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    echo json_encode(["success" => false, "message" => "Product not found"]);
    exit;
}

$upload_dir = __DIR__ . '/../uploads/products/';

function delete_old_image($upload_dir, $image_url) {
    if ($image_url && strpos($image_url, 'uploads/products/') === 0) {
        $old = $upload_dir . basename($image_url);
        if (is_file($old)) unlink($old);
    }
}

if ($remove) {
    delete_old_image($upload_dir, $product['image_url']);
    $conn->prepare("UPDATE products SET image_url = NULL WHERE product_id = :pid")->execute([':pid' => $product_id]);
    audit_log($conn, 'PRODUCT_IMAGE', 'product', $product_id, "Removed image of {$product['name']}");
    echo json_encode(["success" => true, "message" => "Image removed", "image_url" => null]);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "message" => "No image uploaded or upload failed"]);
    exit;
}

if ($_FILES['image']['size'] > 3 * 1024 * 1024) {
    echo json_encode(["success" => false, "message" => "Image must be 3MB or smaller"]);
    exit;
}

$info = @getimagesize($_FILES['image']['tmp_name']);
$allowed = [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG => 'png',
    IMAGETYPE_WEBP => 'webp',
    IMAGETYPE_GIF => 'gif'
];
if ($info === false || !isset($allowed[$info[2]])) {
    echo json_encode(["success" => false, "message" => "File must be a JPG, PNG, WebP or GIF image"]);
    exit;
}

$filename = $product_id . '_' . time() . '.' . $allowed[$info[2]];
if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
    echo json_encode(["success" => false, "message" => "Could not save the image"]);
    exit;
}

delete_old_image($upload_dir, $product['image_url']);

$image_url = 'uploads/products/' . $filename;
$conn->prepare("UPDATE products SET image_url = :url WHERE product_id = :pid")
     ->execute([':url' => $image_url, ':pid' => $product_id]);
audit_log($conn, 'PRODUCT_IMAGE', 'product', $product_id, "Uploaded new image for {$product['name']}");

echo json_encode(["success" => true, "message" => "Image uploaded", "image_url" => $image_url]);
?>
