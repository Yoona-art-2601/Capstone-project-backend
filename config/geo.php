<?php
function haversine_km($lat1, $lng1, $lat2, $lng2) {
    $r = 6371;
    $dlat = deg2rad($lat2 - $lat1);
    $dlng = deg2rad($lng2 - $lng1);
    $a = sin($dlat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dlng / 2) ** 2;
    return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function nominatim_search($params) {
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query($params + ['format' => 'json', 'limit' => 1, 'countrycodes' => 'my']);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_USERAGENT, 'BeaconMart-StudentProject/1.0');
    $response = curl_exec($ch);
    curl_close($ch);
    $data = $response ? json_decode($response, true) : null;
    if (!$data || !isset($data[0]['lat'])) return null;
    return ['lat' => (float)$data[0]['lat'], 'lng' => (float)$data[0]['lon']];
}

function geocode_address($conn, $street, $city, $state, $postcode) {
    set_time_limit(60);
    $deadline = microtime(true) + 25;
    $state_name = preg_replace('/^W\.P\.\s*/', '', $state);
    $attempts = [
        ['q' => "$street, $postcode $city, $state_name, Malaysia"],
        ['q' => "$postcode $city, $state_name, Malaysia"],
        ['postalcode' => $postcode, 'state' => $state_name],
        ['q' => "$city, $state_name, Malaysia"]
    ];
    foreach ($attempts as $i => $params) {
        $key = strtolower(json_encode($params));
        $stmt = $conn->prepare("SELECT latitude, longitude FROM geocode_cache WHERE query = :q");
        $stmt->execute([':q' => substr($key, 0, 255)]);
        $cached = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cached) return ['lat' => (float)$cached['latitude'], 'lng' => (float)$cached['longitude']];

        $found = nominatim_search($params);
        if ($found) {
            $conn->prepare("INSERT IGNORE INTO geocode_cache (query, latitude, longitude) VALUES (:q, :lat, :lng)")
                 ->execute([':q' => substr($key, 0, 255), ':lat' => $found['lat'], ':lng' => $found['lng']]);
            return $found;
        }
        if ($i === count($attempts) - 1 || microtime(true) + 7 > $deadline) break;
        usleep(1100000);
    }
    return null;
}
?>
