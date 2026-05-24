<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$consumerKey = $_ENV['MPESA_CONSUMER_KEY'];
$consumerSecret = $_ENV['MPESA_CONSUMER_SECRET'];

$url = "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";

$credentials = base64_encode($consumerKey . ":" . $consumerSecret);

$headers = [
    "Authorization: Basic " . $credentials,
    "Accept: application/json",
    "Cache-Control: no-cache"
];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");

$response = curl_exec($ch);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

$error = curl_error($ch);

echo "<h2>HTTP CODE</h2>";
echo $httpCode;

echo "<h2>CURL ERROR</h2>";
echo $error ?: "NONE";

echo "<h2>RAW RESPONSE</h2>";

echo "<pre>";
var_dump($response);
echo "</pre>";