<?php

session_start();

require_once '../config/database.php';
require_once '../config/audit.php';

$data = json_decode(file_get_contents("php://input"), true);


if (
    !isset($data['email']) ||
    !isset($data['password'])
) {
    echo json_encode([
        "success" => false,
        "message" => "Missing email or password"
    ]);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];


if ($email === '' || $password === '') {
    echo json_encode([
        "success" => false,
        "message" => "Email and password cannot be empty"
    ]);
    exit;
}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid email format"
    ]);
    exit;
}

try {


    $stmt = $conn->prepare(
        "SELECT user_id, name, email, password, role
         FROM users
         WHERE email = :email"
    );

    $stmt->execute([
        ':email' => $email
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

  
    if (!$user) {
        audit_log($conn, 'LOGIN_FAILED', 'user', null, "Unknown email: $email",
            ['user_id' => null, 'name' => $email, 'role' => null]);
        echo json_encode([
            "success" => false,
            "message" => "Invalid email or password"
        ]);
        exit;
    }

  
    if (!password_verify($password, $user['password'])) {
        audit_log($conn, 'LOGIN_FAILED', 'user', $user['user_id'], "Wrong password for $email", $user);
        echo json_encode([
            "success" => false,
            "message" => "Invalid email or password"
        ]);
        exit;
    }


    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    audit_log($conn, 'LOGIN', 'user', $user['user_id'], "{$user['role']} logged in");

    echo json_encode([
        "success" => true,
        "message" => "Login successful",
        "user" => [
            "user_id" => $user['user_id'],
            "name" => $user['name'],
            "email" => $user['email'],
            "role" => $user['role']
        ]
    ]);

} catch (Exception $e) {

    // show error message
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

?>
