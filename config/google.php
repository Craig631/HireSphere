<?php

require_once __DIR__ . '/../vendor/autoload.php';

$client = new Google_Client();

$client->setClientId('YOUR_GOOGLE_CLIENT_ID');
$client->setClientSecret('YOUR_GOOGLE_CLIENT_KEY');
$client->setRedirectUri("http://localhost/hiresphere/google-callback.php");

$client->addScope("email");
$client->addScope("profile");

?>
