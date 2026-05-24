<?php
require_once "config/stripe.php";
include "config/database.php";

if(session_status() === PHP_SESSION_NONE) session_start();

$session_id = $_GET['session_id'] ?? null;
$booking_id = $_GET['booking_id'] ?? null;
$payment_id = null;
$success = false;

if ($session_id) {
    // Stripe Verification Flow
    try {
        $session = \Stripe\Checkout\Session::retrieve($session_id);
        if ($session->payment_status === 'paid') {
            $booking_id = $session->metadata->booking_id;
            mysqli_query($conn, "UPDATE payments SET status = 'success' WHERE stripe_session_id = '$session_id'");
            if ($booking_id) {
                mysqli_query($conn, "UPDATE bookings SET status = 'confirmed', payment_status = 'paid' WHERE id = " . (int)$booking_id);
                $payment_query = mysqli_query($conn, "SELECT id FROM payments WHERE stripe_session_id = '$session_id' LIMIT 1");
                $payment_row = mysqli_fetch_assoc($payment_query);
                $payment_id = $payment_row['id'] ?? null;
            }
            $success = true;
        }
    } catch (Exception $e) {
        die("Error verifying Stripe payment: " . $e->getMessage());
    }
} elseif ($booking_id) {
    // M-Pesa / Local Verification Flow
    $booking_id = (int)$booking_id;
    $check = mysqli_query($conn, "SELECT payment_status, checkout_request_id FROM bookings WHERE id = $booking_id");
    $res = mysqli_fetch_assoc($check);
    
    if ($res && $res['payment_status'] == 'paid') {
        $success = true;
        // Try to find the associated M-Pesa payment record for the invoice
        $checkout_id = $res['checkout_request_id'];
        $pay_check = mysqli_query($conn, "SELECT id FROM mpesa_payments WHERE checkout_request_id = '$checkout_id' LIMIT 1");
        $pay_res = mysqli_fetch_assoc($pay_check);
        $payment_id = $pay_res['id'] ?? null;
    }
} else {
    die("Payment identification missing.");
}

if (!$success) {
    header("Location: payment-cancel.php");
    exit;
}

include 'includes/header.php';
?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-6">

            <div class="card shadow-lg border-0 rounded-4 text-center p-5">

                <div class="mb-4">

                    <div style="
                        width:90px;
                        height:90px;
                        background:#d1fae5;
                        border-radius:50%;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        margin:auto;
                    ">

                        <i class="bi bi-check-lg"
                           style="font-size:40px;color:#059669;"></i>

                    </div>

                </div>

                <h2 class="fw-bold mb-3">
                    Payment Successful
                </h2>

                <p class="text-muted mb-4">

                    Your booking payment has been confirmed successfully.

                </p>

                <div class="alert alert-light border rounded-3">

                    <strong>Booking Reference</strong><br>

                    <?php
                    $ref_base = $_GET['session_id'] ?? $_GET['booking_id'] ?? 'REF';
                    $reference = strtoupper(substr(md5($ref_base), 0, 10));
                    ?>
                    <span class="badge bg-dark p-3 fs-6">
                        <?php echo $reference; ?>
                    </span>

                </div>

                <div class="d-grid gap-3 mt-4">

                    <?php if ($payment_id): ?>
                        <a href="invoice.php?payment_id=<?php echo $payment_id; ?>"
                           class="btn btn-success btn-lg rounded-pill">

                            Download Invoice

                        </a>
                    <?php endif; ?>
                    <a href="index.php"
                       class="btn btn-dark btn-lg rounded-pill">

                        Back to Homepage

                    </a>

                    <a href="host-dashboard.php"
                       class="btn btn-outline-dark btn-lg rounded-pill">

                        View Dashboard

                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

<?php include 'includes/footer.php'; ?>