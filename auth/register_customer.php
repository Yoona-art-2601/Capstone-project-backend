<?php

require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

// Check required fields
if (
    !isset($data['name']) ||
    !isset($data['email']) ||
    !isset($data['password'])
) {
    echo json_encode([
        "success" => false,
        "message" => "Missing name, email or password"
    ]);
    exit;
}

$name = trim($data['name']);
$email = trim($data['email']);
$password = $data['password'];

// Check empty values
if ($name === '' || $email === '' || $password === '') {
    echo json_encode([
        "success" => false,
        "message" => "Name, email and password cannot be empty"
    ]);
    exit;
}

// Check password length
if (strlen($password) < 8) {
    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 8 characters"
    ]);
    exit;
}

// Check email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid email format"
    ]);
    exit;
}

try {

    // Check whether email already exists
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

    // Hash the password before storing it
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Create Customer account
    $stmt = $conn->prepare(
        "INSERT INTO users (name, email, password, role)
         VALUES (:name, :email, :password, 'Customer')"
    );

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => $hashed_password
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Customer registered successfully"
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => safe_error($e)
    ]);
}

?> 
