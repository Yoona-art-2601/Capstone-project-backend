<?php
function audit_log($conn, $action, $entity_type = null, $entity_id = null, $details = null, $user = null) {
    if ($user === null) {
        $user = [
            'user_id' => $_SESSION['user_id'] ?? null,
            'name' => $_SESSION['name'] ?? null,
            'role' => $_SESSION['role'] ?? null
        ];
    }
    try {
        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, user_name, user_role, action, entity_type, entity_id, details, ip_address)
                                VALUES (:uid, :uname, :urole, :action, :etype, :eid, :details, :ip)");
        $stmt->execute([
            ':uid' => $user['user_id'] ?? null,
            ':uname' => $user['name'] ?? null,
            ':urole' => $user['role'] ?? null,
            ':action' => $action,
            ':etype' => $entity_type,
            ':eid' => $entity_id,
            ':details' => $details,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

function product_name($conn, $product_id) {
    $stmt = $conn->prepare("SELECT name FROM products WHERE product_id = :pid");
    $stmt->execute([':pid' => $product_id]);
    return $stmt->fetchColumn() ?: "Product #$product_id";
}

function branch_name($conn, $branch_id) {
    $stmt = $conn->prepare("SELECT branch_name FROM branches WHERE branch_id = :bid");
    $stmt->execute([':bid' => $branch_id]);
    return $stmt->fetchColumn() ?: "Branch #$branch_id";
}
?>
