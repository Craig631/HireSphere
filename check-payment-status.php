<?php
include 'config/database.php';

$booking_id = $_GET['booking_id'] ?? null;

if (!$booking_id) {
    echo json_encode(['status' => 'error']);
    exit;
}

$stmt = $conn->prepare("SELECT payment_status FROM bookings WHERE id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result) {
    echo json_encode(['status' => $result['payment_status']]);
} else {
    echo json_encode(['status' => 'not_found']);
}
?>