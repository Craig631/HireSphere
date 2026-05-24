<?php

require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Trim values to remove accidental whitespace
$consumerKey = isset($_ENV['MPESA_CONSUMER_KEY']) ? trim($_ENV['MPESA_CONSUMER_KEY']) : '';
$consumerSecret = isset($_ENV['MPESA_CONSUMER_SECRET']) ? trim($_ENV['MPESA_CONSUMER_SECRET']) : '';

echo "<h2>M-Pesa Credentials Debug</h2>";
echo "<p><strong>Consumer Key:</strong> " . htmlspecialchars($consumerKey) . "</p>";
echo "<p><strong>Consumer Key Length:</strong> " . strlen($consumerKey) . "</p>";
echo "<p><strong>Consumer Secret:</strong> " . htmlspecialchars($consumerSecret) . "</p>";
echo "<p><strong>Consumer Secret Length:</strong> " . strlen($consumerSecret) . "</p>";

if (empty($consumerKey) || empty($consumerSecret)) {
    die("<p style='color:red;'>Error: MPESA_CONSUMER_KEY or MPESA_CONSUMER_SECRET is missing in .env</p>");
}

$credentials = base64_encode($consumerKey . ":" . $consumerSecret);
echo "<p><strong>Base64 Credentials:</strong> " . htmlspecialchars($credentials) . "</p>";

// Test authentication
$curl = curl_init();

curl_setopt($curl, CURLOPT_URL, 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $credentials
]);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Only for debugging

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

echo "<p><strong>HTTP Response Code:</strong> " . $httpCode . "</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

$result = json_decode($response);
if (isset($result->access_token)) {
    echo "<p style='color:green;'><strong>SUCCESS!</strong> Access Token: " . htmlspecialchars($result->access_token) . "</p>";
} else {
    echo "<p style='color:red;'><strong>FAILED!</strong> Check credentials above.</p>";
}

?>
