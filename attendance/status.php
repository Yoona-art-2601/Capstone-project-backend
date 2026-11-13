<?php
require_once __DIR__ . '/../config/session.php';
require_once '../config/database.php';
require_once '../config/attendance.php';

require_employee();

try {
    $today = kl_now()->format('Y-m-d');

    $stmt = $conn->prepare("SELECT a.attendance_id, a.branch_id, b.branch_name, a.work_date, a.clock_in, a.clock_out, a.late_minutes, a.early_leave_minutes, a.note
                            FROM attendance a JOIN branches b ON b.branch_id = a.branch_id
                            WHERE a.user_id = :uid ORDER BY a.work_date DESC LIMIT 7");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $todayRecord = null;
    foreach ($recent as $r) {
        if ($r['work_date'] === $today) $todayRecord = $r;
    }

    $branches = $conn->query("SELECT branch_id, branch_name, opening_hours FROM branches ORDER BY branch_id")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "today" => $today,
        "now" => kl_now()->format('Y-m-d H:i:s'),
        "record" => $todayRecord,
        "recent" => $recent,
        "branches" => $branches,
        "grace_minutes" => ATTENDANCE_GRACE_MINUTES
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
