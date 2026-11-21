<?php
require_once '../config/database.php';
require_once '../auth/check_admin.php';
require_once '../config/audit.php';
require_once '../config/geo.php';

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$branch_id = (int)($data['branch_id'] ?? 0);
$name = trim((string)($data['branch_name'] ?? ''));
$address = trim((string)($data['address'] ?? ''));
$phone = trim((string)($data['phone'] ?? ''));
$day_from = $data['open_from'] ?? '';
$day_to = $data['open_to'] ?? '';
$open = (string)($data['open_time'] ?? '');
$close = (string)($data['close_time'] ?? '');
$lat = $data['latitude'] ?? '';
$lng = $data['longitude'] ?? '';
$reset_qr = !empty($data['reset_qr']);

$days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

function fail($message) {
    echo json_encode(["success" => false, "message" => $message]);
    exit;
}

function ampm($hhmm) {
    [$h, $m] = array_map('intval', explode(':', $hhmm));
    return ($h % 12 ?: 12) . ':' . sprintf('%02d', $m) . ($h >= 12 ? 'pm' : 'am');
}

if ($name === '' || mb_strlen($name) > 100) fail("Please enter the branch name (up to 100 characters)");
if ($address === '' || mb_strlen($address) > 255) fail("Please enter the branch address");
if ($phone !== '' && !preg_match('/^\+?[0-9\s-]{9,20}$/', $phone)) fail("Please enter a valid phone number, for example 03-1234 5678");
if (!in_array($day_from, $days, true) || !in_array($day_to, $days, true)) fail("Please choose the opening days");
if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $open) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $close)) fail("Please enter valid opening and closing times");
if ($close <= $open) fail("Closing time must be after opening time");
if (($lat === '') !== ($lng === '')) fail("Enter both latitude and longitude, or leave both empty");
if ($lat !== '' && (!is_numeric($lat) || !is_numeric($lng) || abs($lat) > 90 || abs($lng) > 180)) fail("Latitude or longitude is not valid");

$opening_hours = "$day_from - $day_to, " . ampm($open) . " - " . ampm($close);

try {
    $stmt = $conn->prepare("SELECT branch_name, address, phone, opening_hours, latitude, longitude FROM branches WHERE branch_id = :bid");
    $stmt->execute([':bid' => $branch_id]);
    $old = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$old) fail("Branch not found");

    $warning = '';
    if ($lat === '' && $address !== $old['address']) {
        $found = nominatim_search(['q' => $address]);
        if ($found) {
            $lat = $found['lat'];
            $lng = $found['lng'];
        } else {
            $warning = ' The new address could not be found on the map, so the delivery distance still uses the old location. Enter the latitude and longitude by hand.';
        }
    }
    if ($lat === '') {
        $lat = $old['latitude'];
        $lng = $old['longitude'];
    }

    $sql = "UPDATE branches SET branch_name = :name, address = :address, phone = :phone, opening_hours = :hours, latitude = :lat, longitude = :lng"
         . ($reset_qr ? ", attendance_secret = SHA2(CONCAT(UUID(), RAND(), branch_id), 256)" : "")
         . " WHERE branch_id = :bid";
    $conn->prepare($sql)->execute([
        ':name' => $name, ':address' => $address, ':phone' => $phone === '' ? null : $phone,
        ':hours' => $opening_hours, ':lat' => $lat, ':lng' => $lng, ':bid' => $branch_id
    ]);

    $changes = [];
    if ($old['branch_name'] !== $name) $changes[] = "name: {$old['branch_name']} -> $name";
    if ($old['address'] !== $address) $changes[] = "address updated";
    if (($old['phone'] ?? '') !== $phone) $changes[] = "phone: " . ($old['phone'] ?: '-') . " -> " . ($phone ?: '-');
    if ($old['opening_hours'] !== $opening_hours) $changes[] = "hours: {$old['opening_hours']} -> $opening_hours";
    if ((string)$old['latitude'] !== (string)$lat || (string)$old['longitude'] !== (string)$lng) $changes[] = "map location updated";
    if ($reset_qr) $changes[] = "attendance QR secret reset";
    audit_log($conn, 'BRANCH_UPDATE', 'branch', $branch_id, "Updated $name: " . ($changes ? implode('; ', $changes) : 'no changes'));

    echo json_encode(["success" => true, "message" => "Saved $name." . $warning, "opening_hours" => $opening_hours]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => safe_error($e)]);
}
?>
