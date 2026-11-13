<?php
require_once __DIR__ . '/shop.php';
require_once __DIR__ . '/roles.php';

define('ATTENDANCE_CODE_SECONDS', 120);
define('ATTENDANCE_GRACE_MINUTES', 10);

function require_employee($conn) {
    return require_roles($conn, STAFF_ROLES, "Only staff accounts can use attendance");
}

function attendance_window($offset = 0) {
    return intdiv(time(), ATTENDANCE_CODE_SECONDS) + $offset;
}

function attendance_code($secret, $branch_id, $window) {
    return strtoupper(substr(hash_hmac('sha256', $branch_id . ':' . $window, $secret), 0, 6));
}

function verify_attendance_code($secret, $branch_id, $code) {
    $code = strtoupper(trim((string)$code));
    if ($secret === null || $secret === '' || !preg_match('/^[0-9A-F]{6}$/', $code)) return false;
    foreach ([0, -1] as $offset) {
        if (hash_equals(attendance_code($secret, $branch_id, attendance_window($offset)), $code)) return true;
    }
    return false;
}

function branch_hours($opening_hours) {
    if (!preg_match('/(\d{1,2})(?::(\d{2}))?\s*(am|pm)\s*-\s*(\d{1,2})(?::(\d{2}))?\s*(am|pm)/i', (string)$opening_hours, $m)) return null;
    $to24 = function ($h, $min, $ampm) {
        $h = (int)$h % 12 + (strtolower($ampm) === 'pm' ? 12 : 0);
        return sprintf('%02d:%02d', $h, (int)$min);
    };
    return ['open' => $to24($m[1], $m[2] ?: 0, $m[3]), 'close' => $to24($m[4], $m[5] ?: 0, $m[6])];
}

function minutes_between($from, $to) {
    return (int)floor(($to->getTimestamp() - $from->getTimestamp()) / 60);
}

function late_minutes($opening_hours, DateTime $clock_in) {
    $hours = branch_hours($opening_hours);
    if (!$hours || !in_array((int)$clock_in->format('w'), branch_open_days($opening_hours), true)) return 0;
    $open = new DateTime($clock_in->format('Y-m-d') . ' ' . $hours['open'], $clock_in->getTimezone());
    $late = minutes_between($open, $clock_in);
    return $late > ATTENDANCE_GRACE_MINUTES ? $late : 0;
}

function early_leave_minutes($opening_hours, DateTime $clock_in, DateTime $clock_out) {
    $hours = branch_hours($opening_hours);
    if (!$hours || !in_array((int)$clock_in->format('w'), branch_open_days($opening_hours), true)) return 0;
    $close = new DateTime($clock_in->format('Y-m-d') . ' ' . $hours['close'], $clock_in->getTimezone());
    return max(0, minutes_between($clock_out, $close));
}

function format_minutes($minutes) {
    $minutes = (int)$minutes;
    return $minutes >= 60 ? intdiv($minutes, 60) . "h " . ($minutes % 60) . "m" : "$minutes min";
}

function kl_datetime($value) {
    return new DateTime($value, new DateTimeZone('Asia/Kuala_Lumpur'));
}
?>
