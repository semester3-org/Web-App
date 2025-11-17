<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/midtrans.php");

header('Content-Type: application/json');

// Test data
$transaction = [
    'transaction_details' => [
        'order_id' => 'TEST-' . time(),
        'gross_amount' => 10000
    ],
    'item_details' => [
        [
            'id' => 'ITEM1',
            'price' => 10000,
            'quantity' => 1,
            'name' => 'Test Item'
        ]
    ],
    'customer_details' => [
        'first_name' => 'Test',
        'email' => 'test@example.com',
        'phone' => '08123456789'
    ]
];

$midtrans_url = MIDTRANS_API_URL; // Langsung dari constant
$auth = base64_encode(MIDTRANS_SERVER_KEY . ':');

echo "Testing Midtrans Connection...\n";
echo "URL: " . $midtrans_url . "\n";
echo "Server Key: " . substr(MIDTRANS_SERVER_KEY, 0, 20) . "...\n\n";

$ch = curl_init($midtrans_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . $auth
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transaction));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_VERBOSE, true); // Tambahkan verbose untuk debugging

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo json_encode([
    'http_code' => $http_code,
    'curl_error' => $curl_error,
    'response' => json_decode($response, true),
    'raw_response' => $response,
    'config' => [
        'url' => $midtrans_url,
        'is_production' => MIDTRANS_IS_PRODUCTION,
        'server_key_prefix' => substr(MIDTRANS_SERVER_KEY, 0, 15)
    ]
], JSON_PRETTY_PRINT);
?>
