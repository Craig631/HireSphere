<?php
require_once 'auth/guard.php';
require_once 'config/database.php';
require_once 'includes/mailer.php';

/* Escape names and vehicle details before placing them in HTML. */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$userId = (int) $_SESSION['user_id'];
$paymentId = (int) ($_GET['payment_id'] ?? 0);
$token = (string) ($_GET['token'] ?? '');

$booking = null;
$paymentRecorded = false;
$message = 'Checkout was interrupted. Check your booking before trying again.';

/* Validate this return against the checkout attempt in the customer's session. */
$attempt = $_SESSION['checkout_returns'][$paymentId] ?? null;

$validReturn = is_array($attempt)
    && isset($attempt['token'])
    && $token !== ''
    && hash_equals($attempt['token'], $token);

if ($validReturn) {
    /* Customers can access only their own payment and booking records. */
    $stmt = $conn->prepare(
        "SELECT p.status AS transaction_status,
                b.id AS booking_id, b.vehicle_id, b.status AS booking_status,
                b.payment_status, b.start_date, b.end_date,
                COALESCE(NULLIF(b.customer_name, ''), u.name) AS recipient_name,
                COALESCE(NULLIF(b.customer_email, ''), u.email) AS recipient_email,
                v.vehicle_name
         FROM payments p
         JOIN bookings b ON b.id = p.booking_id
         JOIN users u ON u.id = b.user_id
         JOIN vehicles v ON v.id = b.vehicle_id
         WHERE p.id = ?
           AND p.user_id = ?
           AND b.user_id = ?
           AND p.method = 'stripe'"
    );

    $stmt->bind_param('iii', $paymentId, $userId, $userId);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if ($booking) {
        /* A paid booking must not receive a checkout-cancellation notification. */
        $paymentRecorded = $booking['payment_status'] === 'paid'
            || $booking['transaction_status'] === 'success';

        if ($paymentRecorded) {
            $message = 'A successful payment is already recorded for this booking.';
        } else {
            /*
             * Leaving checkout does not cancel the reservation or prove
             * that a payment failed. Keep existing database statuses intact.
             */
            $message = 'You returned from card checkout without completing it here. '
                . 'Your reservation status has not been changed.';

            /* Avoid resending when this return page is refreshed in the same session. */
            if (empty($attempt['email_sent'])) {
                $name = $escape($booking['recipient_name']);
                $vehicle = $escape($booking['vehicle_name']);
                $pickup = $escape($booking['start_date']);
                $return = $escape($booking['end_date']);
                $reference = 'HS-' . (int) $booking['booking_id'];

                $body = "
                    <div style='font-family:Arial,sans-serif;line-height:1.7;color:#202b40'>
                        <h2 style='color:#0057ff'>Your checkout was interrupted</h2>
                        <p>Hello {$name},</p>
                        <p>You returned to HireSphere from card checkout.</p>
                        <p>At the time of this message, HireSphere had not
                        recorded a successful payment for this booking.</p>
                        <p><strong>Booking:</strong> {$reference}</p>
                        <p><strong>Vehicle:</strong> {$vehicle}</p>
                        <p><strong>Rental dates:</strong> {$pickup} to {$return}</p>
                        <p>Your reservation has not been cancelled by this action.
                        Sign in to HireSphere to check its status and continue
                        payment if needed.</p>
                        <p>If you received a charge notification, check the payment
                        status before attempting another payment.</p>
                        <p>The HireSphere Team</p>
                    </div>
                ";

                $sent = sendEmail(
                    $booking['recipient_email'],
                    "Checkout interrupted — {$reference}",
                    $body
                );

                /* Mark as sent only after the SMTP server accepts the email. */
                if ($sent) {
                    $_SESSION['checkout_returns'][$paymentId]['email_sent'] = true;
                }
            }
        }
    }
}

include 'includes/header.php';
?>

<!-- Display accurate checkout information without promising that no charge occurred. -->
<div class="container py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-4 p-5">

                <div class="display-1 text-warning mb-4">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                </div>

                <h1 class="h2 fw-bold">
                    <?= $paymentRecorded ? 'Payment recorded' : 'Checkout interrupted' ?>
                </h1>

                <p class="text-muted mb-4"><?= $escape($message) ?></p>

                <div class="d-grid gap-2">
                    <?php if ($booking && !$paymentRecorded
                        && in_array(
                            $booking['booking_status'],
                            ['pending', 'approved', 'confirmed'],
                            true
                        )): ?>
                        <!-- Return directly to the correct booking's payment page. -->
                        <a href="payment.php?booking_id=<?= (int) $booking['booking_id'] ?>"
                           class="btn btn-primary">
                            Return to payment
                        </a>
                    <?php endif; ?>

                    <a href="index.php" class="btn btn-outline-secondary">
                        Return to marketplace
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>