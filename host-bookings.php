<?php
require_once 'auth/guard.php';
require_once 'config/database.php';
require_once 'includes/booking-rules.php';

/* Load the email service for booking status notifications. */
require_once __DIR__ . '/includes/mailer.php';

if (($_SESSION['role'] ?? '') !== 'host') {
    http_response_code(403);
    exit('Access denied.');
}

$hostId = (int) $_SESSION['user_id'];
$vehicleId = (int) ($_GET['vehicle_id'] ?? 0);

$stmt = $conn->prepare(
    'SELECT id, vehicle_name FROM vehicles WHERE id = ? AND host_id = ?'
);
$stmt->bind_param('ii', $vehicleId, $hostId);
$stmt->execute();
$vehicle = $stmt->get_result()->fetch_assoc();

if (!$vehicle) {
    http_response_code(404);
    exit('Vehicle not found in your fleet.');
}

if (empty($_SESSION['host_booking_csrf'])) {
    $_SESSION['host_booking_csrf'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(
        $_SESSION['host_booking_csrf'],
        (string) ($_POST['csrf_token'] ?? '')
    )) {
        $error = 'Your form expired. Refresh and try again.';
    } else {
        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $action = (string) ($_POST['action'] ?? '');
        $status = ['approve' => 'approved', 'reject' => 'rejected'][$action] ?? null;

        if (!$status) {
            $error = 'Invalid action.';
        } else {
            $update = $conn->prepare(
                "UPDATE bookings
                 SET status = ?
                 WHERE id = ? AND vehicle_id = ? AND status = 'pending'"
            );
            $update->bind_param('sii', $status, $bookingId, $vehicleId);
            $update->execute();

            /*
             * Send only when this request actually changes a pending booking.
             * Refreshing or repeating the action will not send another notification.
             */
            if ($update->affected_rows === 1) {
                try {
                    /* Read the customer's booking email, with registered email as fallback. */
                    $emailStmt = $conn->prepare(
                        "SELECT b.id, b.start_date, b.end_date, b.payment_status,
                                COALESCE(NULLIF(b.customer_name, ''), u.name) AS recipient_name,
                                COALESCE(NULLIF(b.customer_email, ''), u.email) AS recipient_email,
                                v.vehicle_name
                         FROM bookings b
                         JOIN vehicles v ON v.id = b.vehicle_id
                         JOIN users u ON u.id = b.user_id
                         WHERE b.id = ? AND v.host_id = ?"
                    );

                    $emailStmt->bind_param('ii', $bookingId, $hostId);
                    $emailStmt->execute();
                    $notification = $emailStmt->get_result()->fetch_assoc();

                    if ($notification) {
                        /* Escape all database text included in the HTML email. */
                        $recipientName = hsEscape($notification['recipient_name']);
                        $vehicleName = hsEscape($notification['vehicle_name']);
                        $pickupDate = hsEscape($notification['start_date']);
                        $returnDate = hsEscape($notification['end_date']);
                        $reference = 'HS-' . (int) $notification['id'];

                        if ($status === 'approved') {
                            $subject = "Booking approved — {$reference}";
                            $heading = 'Your booking has been approved';

                            /* Approval and payment are separate steps. */
                            $explanation = $notification['payment_status'] === 'paid'
                                ? 'The host has approved your booking, and your payment is recorded as paid.'
                                : 'The host has approved your booking. Payment has not yet been confirmed. Return to HireSphere to complete checkout.';
                        } else {
                            $subject = "Booking update — {$reference}";
                            $heading = 'Your booking request was rejected';

                            /* Do not promise a refund that the system has not processed. */
                            $explanation = $notification['payment_status'] === 'paid'
                                ? 'The host has rejected this booking. Your payment is recorded as paid; please contact HireSphere about the refund status.'
                                : 'The host has rejected this booking. You can return to HireSphere to choose another vehicle or rental period.';
                        }

                        $body = "
                            <div style='font-family:Arial,sans-serif;line-height:1.7;color:#202b40'>
                                <h2 style='color:#0057ff'>{$heading}</h2>
                                <p>Hello {$recipientName},</p>
                                <p>{$explanation}</p>
                                <p><strong>Reference:</strong> {$reference}</p>
                                <p><strong>Vehicle:</strong> {$vehicleName}</p>
                                <p><strong>Pickup date:</strong> {$pickupDate}</p>
                                <p><strong>Return date:</strong> {$returnDate}</p>
                                <p>The HireSphere Team</p>
                            </div>
                        ";

                        /* A delivery failure must not undo the host's status change. */
                        if (!sendEmail(
                            $notification['recipient_email'],
                            $subject,
                            $body
                        )) {
                            error_log(
                                'HireSphere: booking status email failed for booking '
                                . $bookingId
                            );
                        }
                    }
                } catch (Throwable $exception) {
                    /* Keep notification errors separate from successful booking updates. */
                    error_log('HireSphere booking notification: ' . $exception->getMessage());
                }
            }

            header("Location: host-bookings.php?vehicle_id=$vehicleId");
            exit;
        }
    }
}

$stmt = $conn->prepare(
    "SELECT b.*,
            COALESCE(NULLIF(b.customer_name, ''), u.name) AS renter_name,
            COALESCE(NULLIF(b.customer_email, ''), u.email) AS renter_email
     FROM bookings b
     LEFT JOIN users u ON u.id = b.user_id
     WHERE b.vehicle_id = ?
     ORDER BY b.id DESC"
);
$stmt->bind_param('i', $vehicleId);
$stmt->execute();
$bookings = $stmt->get_result();

include_once 'includes/header.php';
?>

<div class="container py-4">
    <h1 class="h3 fw-bold mb-4">Bookings: <?= hsEscape($vehicle['vehicle_name']) ?></h1>

    <?php if ($error): ?>
        <div class="alert alert-warning"><?= hsEscape($error) ?></div>
    <?php endif; ?>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$bookings->num_rows): ?>
                        <tr><td colspan="5">No bookings yet.</td></tr>
                    <?php endif; ?>

                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                        <tr>
                            <td><?= hsEscape($booking['renter_name']) ?></td>
                            <td><?= hsEscape($booking['renter_email']) ?></td>
                            <td>
                                <?= hsEscape($booking['start_date']) ?>
                                &rarr;
                                <?= hsEscape($booking['end_date']) ?>
                            </td>
                            <td><?= hsEscape(ucfirst($booking['status'])) ?></td>
                            <td>
                                <?php if ($booking['status'] === 'pending'): ?>
                                    <form method="post" class="d-flex gap-2">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= hsEscape($_SESSION['host_booking_csrf']) ?>">
                                        <input type="hidden" name="booking_id"
                                               value="<?= (int) $booking['id'] ?>">
                                        <button name="action" value="approve"
                                                class="btn btn-success btn-sm">Approve</button>
                                        <button name="action" value="reject"
                                                class="btn btn-danger btn-sm">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>