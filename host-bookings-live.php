<?php
include 'config/database.php';

if (!isset($_GET['host_id'])) {
    die(json_encode(['error' => 'Host ID missing']));
}

$host_id = (int)$_GET['host_id'];

$result = mysqli_query($conn,
"SELECT b.*, u.name, v.vehicle_name
 FROM bookings b
 JOIN users u ON b.user_id = u.id
 JOIN vehicles v ON b.vehicle_id = v.id
 WHERE v.host_id = $host_id
 ORDER BY b.id DESC"
);

$data = [];
while($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);