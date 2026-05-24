<?php

include 'config/database.php';

$vehicle_id = $_GET['vehicle_id'];

$query = mysqli_query($conn,
    "SELECT start_date, end_date
     FROM bookings
     WHERE vehicle_id = $vehicle_id
     AND status != 'cancelled'"
);

$disabledDates = [];

while($row = mysqli_fetch_assoc($query)) {

    $start = strtotime($row['start_date']);
    $end = strtotime($row['end_date']);

    while ($start <= $end) {

        $disabledDates[] = date('Y-m-d', $start);

        $start = strtotime("+1 day", $start);
    }
}

echo json_encode($disabledDates);
?>