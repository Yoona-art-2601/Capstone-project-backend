<?php
// Only the React dev server may call the API with cookies (needed for login sessions)
$allowed_origins = ["http://localhost:3000", "http://127.0.0.1:3000"];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header("Access-Control-Allow-Credentials: true");
    header("Vary: Origin");
}
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host = "localhost";
$dbname = "beaconmart_db";
$username = "root";
$password = "";  

function safe_error($e) {
    if ($e instanceof PDOException) {
        error_log("Database error: " . $e->getMessage());
        return "A database error occurred. Please try again";
    }
    return $e->getMessage();
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(["success" => false, "message" => "Cannot connect to the database. Please check that MySQL is running"]));
}
?>