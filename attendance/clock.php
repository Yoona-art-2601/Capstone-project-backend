<?php
require_once __DIR__ . '/../config/session.php';
require_once '../config/database.php';
require_once '../config/audit.php';
require_once '../config/security.php';
require_once '../config/attendance.php';

require_employee();

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$branch_id = (int)($data['branch_id'] ?? 0);
$code = (string)($data['code'] ?? '');
$action = $data['action'] ?? '';

if (!in_array($action, ['in', 'out'], true)) {
    echo json_encode(["success" => false, "message" => "Action must be in or out"]);
    exit;
}

try {
    $email = $_SESSION['email'] ?? '';
    $wait = attempt_lock_minutes($conn, 'attendance_code', $email);
    if ($wait > 0) {
        http_response_code(429);
        echo json_encode(["success" => false, "message" => locked_message($wait)]);
        exit;
    }

    $stmt = $conn->prepare("SELECT branch_id, branch_name, opening_hours, attendance_secret FROM branches WHERE branch_id = :bid");
    $stmt->execute([':bid' => $branch_id]);
    $branch = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$branch) {
        echo json_encode(["success" => false, "message" => "Please choose your branch"]);
        exit;
    }
    if (!verify_attendance_code($branch['attendance_secret'], $branch_id, $code)) {
        record_failed_attempt($conn, 'attendance_code', $email);
        audit_log($conn, 'ATTENDANCE_CODE_FAILED', 'attendance', null, "Wrong or expired attendance code at {$branch['branch_name']}");
        echo json_encode(["success" => false, "message" => "This code is wrong or has expired. Scan the QR code at the branch again"]);
        exit;
    }

    $now = kl_now();
    $today = $now->format('Y-m-d');
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE user_id = :uid AND work_date = :d");
    $stmt->execute([':uid' => $_SESSION['user_id'], ':d' => $today]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    $time = $now->format('Y-m-d H:i:s');

    if ($action === 'in') {
        if ($record) {
            echo json_encode(["success" => false, "message" => "You already clocked in today at " . substr($record['clock_in'], 11, 5)]);
            exit;
        }
        $late = late_minutes($branch['opening_hours'], $now);
        $conn->prepare("INSERT INTO attendance (user_id, branch_id, work_date, clock_in, late_minutes, clock_in_ip) VALUES (:uid, :bid, :d, :t, :late, :ip)")
             ->execute([':uid' => $_SESSION['user_id'], ':bid' => $branch_id, ':d' => $today, ':t' => $time, ':late' => $late, ':ip' => client_ip()]);
        $id = $conn->lastInsertId();
        audit_log($conn, 'ATTENDANCE_IN', 'attendance', $id, "Clocked in at {$branch['branch_name']}" . ($late ? " (" . format_minutes($late) . " late)" : ""));
        $message = "Clocked in at " . $now->format('g:i a') . ($late ? ". You are " . format_minutes($late) . " late." : ". Have a good shift!");
    } else {
        if (!$record) {
            echo json_encode(["success" => false, "message" => "You have not clocked in today"]);
            exit;
        }
        if ($record['clock_out']) {
            echo json_encode(["success" => false, "message" => "You already clocked out today at " . substr($record['clock_out'], 11, 5)]);
            exit;
        }
        if ((int)$record['branch_id'] !== $branch_id) {
            echo json_encode(["success" => false, "message" => "Please clock out at the same branch you clocked in"]);
            exit;
        }
        $early = early_leave_minutes($branch['opening_hours'], kl_datetime($record['clock_in']), $now);
        $conn->prepare("UPDATE attendance SET clock_out = :t, early_leave_minutes = :early, clock_out_ip = :ip WHERE attendance_id = :id")
             ->execute([':t' => $time, ':early' => $early, ':ip' => client_ip(), ':id' => $record['attendance_id']]);
        $worked = minutes_between(kl_datetime($record['clock_in']), $now);
        audit_log($conn, 'ATTENDANCE_OUT', 'attendance', $record['attendance_id'], "Clocked out at {$branch['branch_name']} after " . format_minutes($worked) . ($early ? " (" . format_minutes($early) . " early)" : ""));
        $message = "Clocked out at " . $now->format('g:i a') . ". You worked " . format_minutes($worked) . " today.";
    }

    clear_failed_attempts($conn, 'attendance_code', $email);
    echo json_encode(["success" => true, "message" => $message]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
