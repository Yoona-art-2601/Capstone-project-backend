<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/audit.php';
require_once '../config/security.php';

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$user_id = (int)($data['user_id'] ?? 0);
$name = trim((string)($data['name'] ?? ''));
$email = strtolower(trim((string)($data['email'] ?? '')));
$role = $data['role'] ?? '';
$branch_id = $role === 'Staff' ? (int)($data['branch_id'] ?? 0) : null;
$password = (string)($data['password'] ?? '');

function fail($message) {
    echo json_encode(["success" => false, "message" => $message]);
    exit;
}

if ($name === '' || mb_strlen($name) > 100) fail("Please enter a name (up to 100 characters)");
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail("Please enter a valid email address");
if (!in_array($role, ['Admin', 'Staff', 'Customer'], true)) fail("Please choose a role");
if (!$user_id && ($problem = password_problem($password))) fail($problem);

try {
    if ($role === 'Staff') {
        $stmt = $conn->prepare("SELECT branch_name FROM branches WHERE branch_id = :bid");
        $stmt->execute([':bid' => $branch_id]);
        $branch_name = $stmt->fetchColumn();
        if (!$branch_name) fail("Please choose the branch this staff member works at");
    }

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = :email AND user_id <> :uid");
    $stmt->execute([':email' => $email, ':uid' => $user_id]);
    if ($stmt->fetch()) fail("Another account already uses this email");

    $where_role = $role . ($role === 'Staff' ? " at $branch_name" : '');

    if (!$user_id) {
        $conn->prepare("INSERT INTO users (name, email, password, role, branch_id) VALUES (:name, :email, :pw, :role, :bid)")
             ->execute([':name' => $name, ':email' => $email, ':pw' => password_hash($password, PASSWORD_DEFAULT), ':role' => $role, ':bid' => $branch_id]);
        $new_id = $conn->lastInsertId();
        audit_log($conn, 'USER_CREATE', 'user', $new_id, "Created $where_role account for $name ($email)");
        echo json_encode(["success" => true, "message" => "Account created for $name", "user_id" => (int)$new_id]);
        exit;
    }

    $stmt = $conn->prepare("SELECT u.name, u.email, u.role, u.branch_id, u.is_active, b.branch_name FROM users u LEFT JOIN branches b ON b.branch_id = u.branch_id WHERE u.user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $old = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$old) fail("User not found");

    if ($old['role'] === 'Admin' && $role !== 'Admin') {
        if ($user_id === (int)$_SESSION['user_id']) fail("You cannot remove your own admin role");
        $active_admins = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'Admin' AND is_active = 1")->fetchColumn();
        if ((int)$old['is_active'] && $active_admins <= 1) fail("There must be at least one active admin");
    }

    $conn->prepare("UPDATE users SET name = :name, email = :email, role = :role, branch_id = :bid WHERE user_id = :uid")
         ->execute([':name' => $name, ':email' => $email, ':role' => $role, ':bid' => $branch_id, ':uid' => $user_id]);

    $changes = [];
    if ($old['name'] !== $name) $changes[] = "name: {$old['name']} -> $name";
    if ($old['email'] !== $email) $changes[] = "email: {$old['email']} -> $email";
    $old_role = $old['role'] . ($old['role'] === 'Staff' && $old['branch_name'] ? " at {$old['branch_name']}" : '');
    if ($old_role !== $where_role) $changes[] = "role: $old_role -> $where_role";
    audit_log($conn, 'USER_UPDATE', 'user', $user_id, "Updated $name: " . ($changes ? implode('; ', $changes) : 'no changes'));

    echo json_encode(["success" => true, "message" => "Saved $name"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
