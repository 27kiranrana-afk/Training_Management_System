<?php
// Razorpay Payment Gateway Configuration
// Copy this file to razorpay.php and fill in your actual keys
// Get these from: https://dashboard.razorpay.com > Settings > API Keys

define('RZP_KEY_ID',     'rzp_test_your_key_id_here');
define('RZP_KEY_SECRET', 'your_razorpay_secret_here');
define('RZP_CURRENCY',   'INR');

/**
 * Verify Razorpay payment signature
 */
function rzp_verify_signature($order_id, $payment_id, $signature) {
    $expected = hash_hmac('sha256', $order_id . '|' . $payment_id, RZP_KEY_SECRET);
    return hash_equals($expected, $signature);
}

/**
 * Create a Razorpay order via API
 */
function rzp_create_order($amount_inr, $receipt) {
    $url  = 'https://api.razorpay.com/v1/orders';
    $data = json_encode([
        'amount'   => intval($amount_inr * 100),
        'currency' => RZP_CURRENCY,
        'receipt'  => $receipt,
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERPWD,        RZP_KEY_ID . ':' . RZP_KEY_SECRET);
    curl_setopt($ch, CURLOPT_POST,           true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,     $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER,     ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log('Razorpay order creation failed: ' . $response);
        return false;
    }

    return json_decode($response, true);
}
