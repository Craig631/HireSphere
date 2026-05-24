<?php

include 'config/database.php';
include 'includes/auth.php';

$id = $_GET['id'];
$action = $_GET['action'];

$status = null;
if($action == 'confirm') $status = 'confirmed';
if($action == 'cancel') $status = 'cancelled';

if($status && isset($id)){
    $safe_id = mysqli_real_escape_string($conn, $id);
    $query = "UPDATE bookings SET status='$status' WHERE id=$safe_id";
    mysqli_query($conn, $query);
}

header("Location: host-bookings.php");
exit;

?>