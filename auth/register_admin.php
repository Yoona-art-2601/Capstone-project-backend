<?php

require_once '../config/database.php';

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

// check admin registration code
$correct_admin_code = "bm@admin26";

if ($admin_code !== $correct_admin_code) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid admin registration code"
    ]);
    exit;
}

// check password length
if (strlen($password) < 8) {
    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 8 characters"
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

    echo json_encode([
        "success" => true,
        "message" => "Admin registered successfully"
    ]);

} catch (Exception $e) {

    // show error message
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

?>
