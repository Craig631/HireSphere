<?php
require_once "auth/guard.php";
include "config/database.php";
require_once "config/stripe.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    die("User not logged in");
}

if (!isset($_GET['vehicle_id']) || empty($_GET['vehicle_id'])) {
    die("Vehicle not found. Please go back and try again.");
}

$vehicle_id = (int)$_GET['vehicle_id'];
$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : null;
$user_id = (int)$user_id; // Cast to int after null check
// Fetch vehicle details from DB
$stmt = mysqli_prepare($conn,
    "SELECT vehicle_name, price_per_day FROM vehicles WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $vehicle_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    die("Invalid vehicle.");
}

$vehicle = mysqli_fetch_assoc($result);

$vehicle_name = $vehicle['vehicle_name'];

// Fallback if amount wasn't passed in URL correctly
if ($amount <= 0) {
    $amount = (float)$vehicle['price_per_day'];
}

if (isset($_POST['pay'])) {
    $method = $_POST['method'];

    if ($method == "stripe") {
        // Insert record first to get the payment_id for the success URL
        $stmt_pay = mysqli_prepare($conn, "INSERT INTO payments (user_id, vehicle_id, amount, method, status) VALUES (?, ?, ?, 'stripe', 'pending')");
        mysqli_stmt_bind_param($stmt_pay, "iid", $user_id, $vehicle_id, $amount);
        mysqli_stmt_execute($stmt_pay);
        
        $payment_id = mysqli_insert_id($conn);
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'kes',
                    'product_data' => [
                        'name' => 'Vehicle Booking Payment'
                    ],
                    'unit_amount' => $amount * 100,
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'booking_id' => $booking_id,
                'user_id' => $user_id,
                'vehicle_id' => $vehicle_id
            ],
            'mode' => 'payment',
            'success_url' => 'http://localhost/hiresphere/payment-success.php?payment_id=' . $payment_id . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => "http://localhost/hiresphere/payment-cancel.php",
        ]);

        $session_id = $session->id;
        
        // Update the record with the stripe_session_id
        mysqli_query($conn, "UPDATE payments SET stripe_session_id = '$session_id' WHERE id = $payment_id");

        header("Location: " . $session->url);
        exit;
    } elseif ($method == "mpesa") {
        $mpesa_phone = $_POST['mpesa_phone'] ?? '';
        if (!empty($mpesa_phone) && $booking_id) {
            // Update the booking with the specific M-Pesa phone number provided in the form
            $stmt_update = mysqli_prepare($conn, "UPDATE bookings SET customer_phone = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_update, "si", $mpesa_phone, $booking_id);
            mysqli_stmt_execute($stmt_update);
            
            // Redirect to trigger the M-Pesa STK Push
            header("Location: mpesa-stk.php?booking_id=" . $booking_id);
            exit;
        }
    }

    // Prepared statement for other methods
    $stmt_alt = mysqli_prepare($conn, "INSERT INTO payments (user_id, vehicle_id, amount, method, status) VALUES (?, ?, ?, ?, 'pending')");
    mysqli_stmt_bind_param($stmt_alt, "iids", $user_id, $vehicle_id, $amount, $method);
    mysqli_stmt_execute($stmt_alt);

    $payment_msg = "Payment initiated using " . htmlspecialchars($method);
    
    // If M-Pesa, you would typically trigger your mpesa-stk.php logic here
}

?>

<?php include 'includes/header.php'; ?>

<style>
    .payment-option {
        display: block;
        cursor: pointer;
        margin-bottom: 15px;
    }

    .payment-option input {
        display: none;
    }

    .payment-card {
        border: 2px solid #f0f0f0;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
        background: #fff;
    }

    .payment-option input:checked + .payment-card {
        border-color: #0000FF;
        background-color: #f0f4ff;
        box-shadow: 0 4px 12px rgba(0, 0, 255, 0.1);
    }

    .payment-logo {
        height: 24px;
        width: 40px;
        object-fit: contain;
        margin-right: 15px;
    }

    .payment-icon {
        font-size: 20px;
        margin-right: 15px;
        width: 40px;
        text-align: center;
    }

    .payment-card span {
        font-weight: 600;
        color: #333;
    }

    .payment-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        border: 1px solid #e9ecef;
        margin-top: 20px;
    }

    .form-control {
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #ddd;
    }

    .form-control:focus {
        border-color: #0000FF;
        box-shadow: 0 0 0 0.25rem rgba(0, 0, 255, 0.1);
    }

    .btn-pay {
        background-color: #0000FF;
        border: none;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .btn-pay:hover {
        background-color: #0000cc;
    }
</style>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-6">

            <div class="card shadow-lg border-0 rounded-4">

                <div class="card-body p-5">

                    <h2 class="mb-4 fw-bold text-center">
                        Checkout
                    </h2>

                    <div class="mb-4">

                        <h5 class="fw-semibold">
                            Vehicle:
                        </h5>

                        <p class="text-muted">
                            <?php echo $vehicle_name; ?>
                        </p>

                    </div>

                    <div class="mb-4">

                        <h5 class="fw-semibold">
                            Amount:
                        </h5>

                        <p class="fs-4 fw-bold text-success">
                            KES <?php echo number_format($amount); ?>
                        </p>

                    </div>

                    <form method="POST" action="">

                        <!-- Hidden inputs required by stripe-checkout.php -->
                        <input type="hidden" name="vehicle_id" value="<?php echo $vehicle_id; ?>">
                        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                        <input
                            type="hidden"
                            name="vehicle_name"
                            value="<?php echo $vehicle_name; ?>"
                        >

                        <input
                            type="hidden"
                            name="amount"
                            value="<?php echo $amount; ?>"
                        >

                        <div class="mb-4">

                            <label class="form-label fw-semibold mb-3">
                                Choose Payment Method
                            </label>

                            <div class="payment-methods">

                                <!-- M-Pesa -->
                                <label class="payment-option">

                                    <input type="radio" name="method" value="mpesa" required>

                                    <div class="payment-card">

                                        <img
                                            src="https://upload.wikimedia.org/wikipedia/commons/1/15/M-PESA_LOGO-01.svg"
                                            class="payment-logo"
                                            alt="M-Pesa"
                                        >
                                        <span>M-Pesa</span>

                                    </div>

                                </label>

                                <!-- Card -->
                                <label class="payment-option">

                                    <input type="radio" name="method" value="stripe">

                                    <div class="payment-card">

                                        <div class="payment-icon">
                                            💳
                                        </div>

                                        <span>
                                            Credit / Debit Card
                                        </span>

                                    </div>

                                </label>

                                <!-- PayPal -->
                                <label class="payment-option">

                                    <input type="radio" name="method" value="paypal">

                                    <div class="payment-card">

                                        <img
                                            src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg"
                                            class="payment-logo"
                                            alt="PayPal"
                                        >
                                        <span>PayPal</span>

                                    </div>

                                </label>

                            </div>

                        </div>

                        <!-- M-Pesa Section -->
                        <div id="mpesa-section" class="payment-section mb-4 d-none">

                            <label class="form-label fw-semibold">
                                M-Pesa Phone Number
                            </label>

                            <input
                                type="text"
                                name="mpesa_phone"
                                class="form-control"
                                placeholder="07XXXXXXXX"
                            >

                        </div>

                        <!-- Card Section -->
                        <div id="card-section" class="payment-section mb-4 d-none">

                            <div class="mb-3">

                                <label class="form-label fw-semibold">
                                    Card Number
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    placeholder="1234 5678 9012 3456"
                                >

                            </div>

                            <div class="row">

                                <div class="col-md-6">

                                    <label class="form-label fw-semibold">
                                        Expiry Date
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        placeholder="MM/YY"
                                    >

                                </div>

                                <div class="col-md-6">

                                    <label class="form-label fw-semibold">
                                        CVV
                                    </label>

                                    <input
                                        type="password"
                                        class="form-control"
                                        placeholder="123"
                                    >

                                </div>

                            </div>

                        </div>

                        <button name="pay" class="btn btn-primary btn-pay w-100 py-3 mt-3">

                            Pay Now

                        </button>

                    </form>

                    <?php if (isset($payment_msg)): ?>
                        <div class="alert alert-info mt-4">
                            <?php echo $payment_msg; ?>
                        </div>
                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>

<script>

const paymentOptions = document.querySelectorAll(
    'input[name="method"]'
);

const mpesaSection =
    document.getElementById('mpesa-section');

const cardSection =
    document.getElementById('card-section');

paymentOptions.forEach(option => {

    option.addEventListener('change', function(){

        mpesaSection.classList.add('d-none');
        cardSection.classList.add('d-none');

        if(this.value === 'mpesa'){
            mpesaSection.classList.remove('d-none');
        }

        if(this.value === 'stripe'){
            cardSection.classList.remove('d-none');
        }

    });

});

</script>

<?php include 'includes/footer.php'; ?>