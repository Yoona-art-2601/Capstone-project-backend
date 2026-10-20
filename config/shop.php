<?php
define('FREE_DELIVERY_THRESHOLD', 120.00);
define('DELIVERY_BASE_FEE', 5.00);
define('DELIVERY_BASE_KM', 5);
define('DELIVERY_PER_KM', 1.00);
define('DELIVERY_MAX_KM', 30);
define('SST_RATE', 0.06);
define('WHATSAPP_NUMBER', '60163334371');
define('TIME_SLOTS', ['10:00-12:00', '12:00-14:00', '14:00-16:00', '16:00-18:00']);
define('SAME_DAY_CUTOFF', '15:00');
define('SLOT_LEAD_MINUTES', 60);
define('SLOT_DAYS_AHEAD', 7);
define('MALAYSIA_STATES', [
    'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Perak', 'Perlis',
    'Pulau Pinang', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu',
    'W.P. Kuala Lumpur', 'W.P. Labuan', 'W.P. Putrajaya'
]);

function effective_price($price, $sale_price) {
    if ($sale_price !== null && $sale_price !== '' && (float)$sale_price < (float)$price) {
        return (float)$sale_price;
    }
    return (float)$price;
}

function delivery_fee_for_distance($subtotal, $distance_km) {
    if ($subtotal >= FREE_DELIVERY_THRESHOLD) return 0.00;
    $extra_km = max(0, ceil($distance_km - DELIVERY_BASE_KM));
    return round(DELIVERY_BASE_FEE + $extra_km * DELIVERY_PER_KM, 2);
}

function sst_for($subtotal) {
    return round($subtotal * SST_RATE, 2);
}

function kl_now() {
    return new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
}

function branch_open_days($opening_hours) {
    $names = ['Sun' => 0, 'Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5, 'Sat' => 6];
    if (!preg_match('/(Sun|Mon|Tue|Wed|Thu|Fri|Sat)\w*\s*-\s*(Sun|Mon|Tue|Wed|Thu|Fri|Sat)/i', (string)$opening_hours, $m)) {
        return [1, 2, 3, 4, 5, 6];
    }
    $from = $names[ucfirst(strtolower(substr($m[1], 0, 3)))];
    $to = $names[ucfirst(strtolower(substr($m[2], 0, 3)))];
    $days = [];
    for ($d = $from; ; $d = ($d + 1) % 7) {
        $days[] = $d;
        if ($d === $to) break;
    }
    return $days;
}

function available_slots($opening_hours) {
    $now = kl_now();
    $open_days = branch_open_days($opening_hours);
    $cutoff = DateTime::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . SAME_DAY_CUTOFF, new DateTimeZone('Asia/Kuala_Lumpur'));
    $result = [];
    for ($i = 0; $i < SLOT_DAYS_AHEAD; $i++) {
        $day = (clone $now)->modify("+$i day");
        if (!in_array((int)$day->format('w'), $open_days, true)) continue;
        $times = [];
        foreach (TIME_SLOTS as $slot) {
            if ($i === 0) {
                if ($now >= $cutoff) break;
                $start = DateTime::createFromFormat('Y-m-d H:i', $day->format('Y-m-d') . ' ' . substr($slot, 0, 5), new DateTimeZone('Asia/Kuala_Lumpur'));
                if ($start->getTimestamp() - $now->getTimestamp() < SLOT_LEAD_MINUTES * 60) continue;
            }
            $times[] = $slot;
        }
        if ($times) {
            $result[] = ['date' => $day->format('Y-m-d'), 'label' => $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : $day->format('D, j M')), 'slots' => $times];
        }
    }
    return $result;
}

function slot_is_available($opening_hours, $date, $time) {
    foreach (available_slots($opening_hours) as $day) {
        if ($day['date'] === $date && in_array($time, $day['slots'], true)) return true;
    }
    return false;
}
?>
