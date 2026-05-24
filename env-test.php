<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "<pre>";

echo "KEY: " . $_ENV['MPESA_CONSUMER_KEY'] . "<br><br>";

echo "SECRET: " . $_ENV['MPESA_CONSUMER_SECRET'] . "<br><br>";

echo "SHORTCODE: " . $_ENV['MPESA_SHORTCODE'] . "<br><br>";

echo "PASSKEY: " . $_ENV['MPESA_PASSKEY'] . "<br><br>";

echo "</pre>";

?>