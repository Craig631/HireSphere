<?php
require_once 'auth/guard.php';
require_once 'config/database.php';

$hostId = (int) $_SESSION['user_id'];
$bookingId = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare(
    'SELECT b.vehicle_id
     FROM bookings b
     JOIN vehicles v ON v.id = b.vehicle_id
     WHERE b.id = ? AND v.host_id = ?'
);
$stmt->bind_param('ii', $bookingId, $hostId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking || ($_SESSION['role'] ?? '') !== 'host') {
    http_response_code(403);
    exit('Access denied.');
}

header('Location: host-bookings.php?vehicle_id=' . (int) $booking['vehicle_id']);
exit;