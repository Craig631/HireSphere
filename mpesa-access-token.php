<?php

require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Trim values to remove accidental whitespace from .env copy-pasting
$consumerKey = isset($_ENV['MPESA_CONSUMER_KEY']) ? trim($_ENV['MPESA_CONSUMER_KEY']) : '';
$consumerSecret = isset($_ENV['MPESA_CONSUMER_SECRET']) ? trim($_ENV['MPESA_CONSUMER_SECRET']) : '';

if (empty($consumerKey) || empty($consumerSecret)) {
    die(json_encode(["error" => "MPESA_CONSUMER_KEY or MPESA_CONSUMER_SECRET is missing in .env"]));
}

$credentials = base64_encode($consumerKey . ":" . $consumerSecret);

$curl = curl_init();

curl_setopt($curl, CURLOPT_URL,
'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');

curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $credentials
]);

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for testing

$response = curl_exec($curl);

curl_close($curl);

$result = json_decode($response);

if (isset($result->access_token)) {
    echo $result->access_token;
} else {
    header('Content-Type: application/json');
    echo $response; // Return the error from Safaricom
    exit;
}
?>