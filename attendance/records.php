<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/attendance.php';

$per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$format = $_GET['format'] ?? 'json';
$valid_date = fn($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && DateTime::createFromFormat('Y-m-d', $d);
$date_to = $valid_date($_GET['date_to'] ?? null) ? $_GET['date_to'] : kl_now()->format('Y-m-d');
$date_from = $valid_date($_GET['date_from'] ?? null) ? $_GET['date_from'] : kl_datetime($date_to . ' -6 days')->format('Y-m-d');
if ($date_from > $date_to) [$date_from, $date_to] = [$date_to, $date_from];

$where = ["a.work_date BETWEEN :from AND :to"];
$params = [':from' => $date_from, ':to' => $date_to];
$branch_id = (int)($_GET['branch_id'] ?? 0);
if ($branch_id) {
    $where[] = "a.branch_id = :bid";
    $params[':bid'] = $branch_id;
}
$user_id = (int)($_GET['user_id'] ?? 0);
if ($user_id) {
    $where[] = "a.user_id = :uid";
    $params[':uid'] = $user_id;
}
$filters = [
    'late' => "a.late_minutes > 0",
    'early' => "a.early_leave_minutes > 0",
    'missing' => "a.clock_out IS NULL AND a.work_date < CURDATE()",
    'working' => "a.clock_out IS NULL AND a.work_date = CURDATE()"
];
$status = $_GET['status'] ?? '';
if (isset($filters[$status])) $where[] = $filters[$status];
$where_sql = implode(' AND ', $where);

$worked = "CASE WHEN a.clock_out IS NULL THEN NULL ELSE TIMESTAMPDIFF(MINUTE, a.clock_in, a.clock_out) END";
$select = "SELECT a.attendance_id, a.user_id, u.name AS staff_name, u.role, a.branch_id, b.branch_name, a.work_date, a.clock_in, a.clock_out,
                  a.late_minutes, a.early_leave_minutes, $worked AS worked_minutes, a.note
           FROM attendance a
           JOIN users u ON u.user_id = a.user_id
           JOIN branches b ON b.branch_id = a.branch_id
           WHERE $where_sql
           ORDER BY a.work_date DESC, a.clock_in DESC";

try {
    if ($format === 'csv') {
        $stmt = $conn->prepare($select);
        $stmt->execute($params);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="beacon-mart-attendance-' . $date_from . '-to-' . $date_to . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Beacon Mart attendance', "$date_from to $date_to"]);
        fputcsv($out, []);
        fputcsv($out, ['Date', 'Staff', 'Branch', 'Clock in', 'Clock out', 'Hours worked', 'Late (min)', 'Left early (min)', 'Note']);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            fputcsv($out, [
                $r['work_date'], $r['staff_name'], $r['branch_name'], substr($r['clock_in'], 11, 5),
                $r['clock_out'] ? substr($r['clock_out'], 11, 5) : 'Missing',
                $r['worked_minutes'] === null ? '' : number_format($r['worked_minutes'] / 60, 2),
                $r['late_minutes'], $r['early_leave_minutes'], $r['note']
            ]);
        }
        fclose($out);
        exit;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) FROM attendance a WHERE $where_sql");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $stmt = $conn->prepare($select . " LIMIT $per_page OFFSET " . (($page - 1) * $per_page));
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("
        SELECT a.user_id, u.name AS staff_name,
               COUNT(*) AS days,
               COALESCE(SUM($worked), 0) AS worked_minutes,
               SUM(a.late_minutes > 0) AS late_days,
               COALESCE(SUM(a.late_minutes), 0) AS late_minutes,
               SUM(a.early_leave_minutes > 0) AS early_days,
               SUM(a.clock_out IS NULL AND a.work_date < CURDATE()) AS missing_clock_out
        FROM attendance a JOIN users u ON u.user_id = a.user_id
        WHERE $where_sql
        GROUP BY a.user_id, u.name
        ORDER BY u.name
    ");
    $stmt->execute($params);
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $staff = $conn->query("SELECT user_id, name, role FROM users WHERE role <> 'Customer' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "date_from" => $date_from,
        "date_to" => $date_to,
        "page" => $page,
        "pages" => max(1, (int)ceil($total / $per_page)),
        "total" => $total,
        "data" => $rows,
        "summary" => $summary,
        "staff" => $staff
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
