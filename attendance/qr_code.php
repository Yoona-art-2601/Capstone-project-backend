<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/attendance.php';

$branch_id = (int)($_GET['branch_id'] ?? 0);

try {
    $stmt = $conn->prepare("SELECT branch_id, branch_name, opening_hours, attendance_secret FROM branches WHERE branch_id = :bid");
    $stmt->execute([':bid' => $branch_id]);
    $branch = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$branch) {
        echo json_encode(["success" => false, "message" => "Branch not found"]);
        exit;
    }
    if (!$branch['attendance_secret']) {
        echo json_encode(["success" => false, "message" => "Attendance is not set up for this branch. Run database/attendance.sql"]);
        exit;
    }

    $window = attendance_window();
    echo json_encode([
        "success" => true,
        "branch_id" => (int)$branch['branch_id'],
        "branch_name" => $branch['branch_name'],
        "opening_hours" => $branch['opening_hours'],
        "code" => attendance_code($branch['attendance_secret'], $branch['branch_id'], $window),
        "expires_in" => ($window + 1) * ATTENDANCE_CODE_SECONDS - time(),
        "period" => ATTENDANCE_CODE_SECONDS
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
