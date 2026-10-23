<?php

require_once 'config/database.php';

$vehicleId = (int) ($_GET['vehicle_id'] ?? 0);
$stmt = $conn->prepare(
        "SELECT start_date, end_date
         FROM bookings
         WHERE vehicle_id = ?
             AND status IN ('pending', 'approved', 'confirmed')"
);
$stmt->bind_param('i', $vehicleId);
$stmt->execute();
$query = $stmt->get_result();

$disabledDates = [];

while ($row = $query->fetch_assoc()) {

    $start = strtotime($row['start_date']);
    $end = strtotime($row['end_date']);

    while ($start <= $end) {

        $disabledDates[] = date('Y-m-d', $start);

        $start = strtotime("+1 day", $start);
    }
}

echo json_encode($disabledDates);