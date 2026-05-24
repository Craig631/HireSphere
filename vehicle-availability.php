<?php
include "config/database.php";

$vehicle_id = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;

$result = mysqli_query($conn,
    "SELECT start_date, end_date 
     FROM bookings 
     WHERE vehicle_id = $vehicle_id 
     AND status IN ('pending', 'approved', 'confirmed')"
);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);