<?php
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY_HERE');
define('BACKEND_URL', 'http://localhost/beaconmart-project/backend');
define('FRONTEND_URL', 'http://localhost:3000');

function stripe_request($method, $path, $params = []) {
    $ch = curl_init('https://api.stripe.com/v1/' . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }

    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("Stripe connection error: " . $error);
    }
    curl_close($ch);

    return json_decode($response, true);
}
?>