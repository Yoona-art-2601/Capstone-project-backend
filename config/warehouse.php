<?php
define('MAIN_WAREHOUSE_ID', 1);

function parse_local_datetime($value, $fallback = 'now') {
    $value = trim((string)$value);
    $tz = new DateTimeZone('Asia/Kuala_Lumpur');
    if ($value === '') return (new DateTime($fallback, $tz))->format('Y-m-d H:i:s');
    foreach (['Y-m-d\TH:i', 'Y-m-d H:i', 'Y-m-d H:i:s', 'Y-m-d\TH:i:s'] as $format) {
        $d = DateTime::createFromFormat($format, $value, $tz);
        if ($d) return $d->format('Y-m-d H:i:s');
    }
    return null;
}

function product_exists($conn, $product_id) {
    $stmt = $conn->prepare("SELECT name FROM products WHERE product_id = :pid AND is_active = 1");
    $stmt->execute([':pid' => $product_id]);
    return $stmt->fetchColumn();
}
?>
