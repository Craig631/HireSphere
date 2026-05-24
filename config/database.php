<?php

$host = "localhost";
$user = "hiresphere_user";
$password = "8964";
$database = "hiresphere";

$conn = mysqli_connect(
    $host,
    $user,
    $password,
    $database
);

if(!$conn){

    die("Database connection failed: " . mysqli_connect_error());

}

?>