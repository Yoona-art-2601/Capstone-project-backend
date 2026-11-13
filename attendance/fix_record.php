<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/audit.php';
require_once '../config/attendance.php';
require_once '../config/warehouse.php';

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$attendance_id = (int)($data['attendance_id'] ?? 0);
$note = trim((string)($data['note'] ?? ''));
$raw_clock_out = trim((string)($data['clock_out'] ?? ''));
$clock_out = $raw_clock_out === '' ? null : parse_local_datetime($raw_clock_out);

if ($note === '') {
    echo json_encode(["success" => false, "message" => "Please give a reason for the correction"]);
    exit;
}
if ($clock_out === null) {
    echo json_encode(["success" => false, "message" => "Please enter a valid clock-out time"]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT a.*, u.name AS staff_name, b.opening_hours FROM attendance a
                            JOIN users u ON u.user_id = a.user_id JOIN branches b ON b.branch_id = a.branch_id
                            WHERE a.attendance_id = :id");
    $stmt->execute([':id' => $attendance_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$record) {
        echo json_encode(["success" => false, "message" => "Attendance record not found"]);
        exit;
    }

    $in = kl_datetime($record['clock_in']);
    $out = kl_datetime($clock_out);
    if ($out <= $in) {
        echo json_encode(["success" => false, "message" => "Clock-out must be after the clock-in time " . $in->format('g:i a')]);
        exit;
    }
    if ($out->format('Y-m-d') !== $in->format('Y-m-d')) {
        echo json_encode(["success" => false, "message" => "Clock-out must be on the same day as the clock-in"]);
        exit;
    }
    if ($out > kl_now()) {
        echo json_encode(["success" => false, "message" => "Clock-out cannot be in the future"]);
        exit;
    }

    $early = early_leave_minutes($record['opening_hours'], $in, $out);
    $full_note = mb_substr($note . ' (corrected by ' . ($_SESSION['name'] ?? 'Admin') . ')', 0, 255);
    $conn->prepare("UPDATE attendance SET clock_out = :t, early_leave_minutes = :early, note = :note WHERE attendance_id = :id")
         ->execute([':t' => $out->format('Y-m-d H:i:s'), ':early' => $early, ':note' => $full_note, ':id' => $attendance_id]);

    audit_log($conn, 'ATTENDANCE_FIX', 'attendance', $attendance_id,
        "Set clock-out of {$record['staff_name']} on {$record['work_date']} to " . $out->format('H:i')
        . ($record['clock_out'] ? " (was " . substr($record['clock_out'], 11, 5) . ")" : " (was missing)") . ": $note");

    echo json_encode(["success" => true, "message" => "Clock-out updated for {$record['staff_name']}"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
