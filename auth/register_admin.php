<?php

require_once '../config/database.php';
require_once '../config/audit.php';
require_once '../config/admin.php';
require_once '../config/security.php';

$data = json_decode(file_get_contents("php://input"), true);

// check required fields
if (
    !isset($data['name']) ||
    !isset($data['email']) ||
    !isset($data['password']) ||
    !isset($data['admin_code'])
) {
    echo json_encode([
        "success" => false,
        "message" => "Missing name, email, password or admin code"
    ]);
    exit;
}

$name = trim($data['name']);
$email = trim($data['email']);
$password = $data['password'];
$admin_code = trim($data['admin_code']);

// check empty fields
if (
    $name === '' ||
    $email === '' ||
    $password === '' ||
    $admin_code === ''
) {
    echo json_encode([
        "success" => false,
        "message" => "Name, email, password and admin code cannot be empty"
    ]);
    exit;
}

$wait = attempt_lock_minutes($conn, 'admin_code');
if ($wait > 0) {
    http_response_code(429);
    echo json_encode([
        "success" => false,
        "message" => locked_message($wait)
    ]);
    exit;
}

// check admin registration code
if (!hash_equals(ADMIN_REGISTER_CODE, $admin_code)) {
    record_failed_attempt($conn, 'admin_code', $email);
    audit_log($conn, 'ADMIN_CODE_FAILED', 'user', null, "Wrong admin registration code for $email",
        ['user_id' => null, 'name' => $email, 'role' => null]);
    echo json_encode([
        "success" => false,
        "message" => "Invalid admin registration code"
    ]);
    exit;
}

// check password length
if ($problem = password_problem($password)) {
    echo json_encode([
        "success" => false,
        "message" => $problem
    ]);
    exit;
}

// check email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid email format"
    ]);
    exit;
}

try {

    // check email already registered
    $stmt = $conn->prepare(
        "SELECT user_id FROM users WHERE email = :email"
    );

    $stmt->execute([
        ':email' => $email
    ]);

    if ($stmt->fetch()) {
        echo json_encode([
            "success" => false,
            "message" => "Email already registered"
        ]);
        exit;
    }

    // hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // create admin account
    $stmt = $conn->prepare(
        "INSERT INTO users (name, email, password, role)
         VALUES (:name, :email, :password, 'Admin')"
    );

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => $hashed_password
    ]);

    $new_id = $conn->lastInsertId();
    audit_log($conn, 'REGISTER_ADMIN', 'user', $new_id, "New admin account: $name ($email)",
        ['user_id' => $new_id, 'name' => $name, 'role' => 'Admin']);

    echo json_encode([
        "success" => true,
        "message" => "Admin registered successfully"
    ]);

} catch (Exception $e) {

    // show error message
    echo json_encode([
        "success" => false,
        "message" => safe_error($e)
    ]);
}

?>
