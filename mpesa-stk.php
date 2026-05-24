<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

$booking_id = $_GET['booking_id'] ?? null;

if (!$booking_id) {
    die("Booking ID is required");
}

/*
|--------------------------------------------------------------------------
| LOAD ENV
|--------------------------------------------------------------------------
*/

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

/*
|--------------------------------------------------------------------------
| CREDENTIALS
|--------------------------------------------------------------------------
*/

$consumerKey = $_ENV['MPESA_CONSUMER_KEY'];
$consumerSecret = $_ENV['MPESA_CONSUMER_SECRET'];

$BusinessShortCode = $_ENV['MPESA_SHORTCODE'];
$Passkey = $_ENV['MPESA_PASSKEY'];

/*
|--------------------------------------------------------------------------
| FETCH BOOKING FROM DATABASE
|--------------------------------------------------------------------------
*/

$conn = new mysqli("localhost", "hiresphere_user", "8964", "hiresphere");

$stmt = $conn->prepare("SELECT customer_phone, paid_amount FROM bookings WHERE id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

$booking = $result->fetch_assoc();

if (!$booking) {
    die("Booking not found");
}

$phone = $booking['customer_phone'];
$amount = (int)$booking['paid_amount'];

// remove spaces
$phone = trim($phone);

// replace leading 0 with 254
if (substr($phone, 0, 1) == "0") {
    $phone = "254" . substr($phone, 1);
}

// ensure no + sign
$phone = str_replace("+", "", $phone);


/*
|--------------------------------------------------------------------------
| GENERATE ACCESS TOKEN
|--------------------------------------------------------------------------
*/

$tokenUrl =
"https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";

$credentials = base64_encode($consumerKey . ":" . $consumerSecret);

$headers = [
    "Authorization: Basic $credentials",
    "Accept: application/json"
];

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => $tokenUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($curl);

$data = json_decode($response);

if (!isset($data->access_token)) {

    die("FAILED TO GET ACCESS TOKEN");
}

$accessToken = $data->access_token;

/*
|--------------------------------------------------------------------------
| STK PASSWORD
|--------------------------------------------------------------------------
*/

$Timestamp = date('YmdHis');

$Password = base64_encode(
    $BusinessShortCode .
    $Passkey .
    $Timestamp
);

/*
|--------------------------------------------------------------------------
| STK PUSH
|--------------------------------------------------------------------------
*/

$stkUrl =
"https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest";

$callback =
"https://embellish-theft-cabdriver.ngrok-free.dev/hiresphere/mpesa-callback.php";

$stkHeaders = [
    "Content-Type: application/json",
    "Authorization: Bearer $accessToken"
];

$payload = [

    "BusinessShortCode" => $BusinessShortCode,

    "Password" => $Password,

    "Timestamp" => $Timestamp,

    "TransactionType" => "CustomerPayBillOnline",

    "Amount" => $amount > 0 ? $amount : 1, // Use actual amount, fallback to 1 for safety

    "PartyA" => $phone,

    "PartyB" => $BusinessShortCode,

    "PhoneNumber" => $phone,

    "CallBackURL" => $callback,

    "AccountReference" => "HireSphere",

    "TransactionDesc" => "Vehicle Booking Payment"
];

$ch = curl_init();

curl_setopt_array($ch, [

    CURLOPT_URL => $stkUrl,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_POST => true,

    CURLOPT_POSTFIELDS => json_encode($payload),

    CURLOPT_HTTPHEADER => $stkHeaders,

    CURLOPT_SSL_VERIFYPEER => false,

    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($ch);

$data = json_decode($response);
$checkoutRequestID = $data->CheckoutRequestID ?? null;

if ($checkoutRequestID) {
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET checkout_request_id = ?
        WHERE id = ?
    ");
    $stmt->bind_param("si", $checkoutRequestID, $booking_id);
    $stmt->execute();
}

$error = curl_error($ch);

/*
|--------------------------------------------------------------------------
| OUTPUT
|--------------------------------------------------------------------------
*/
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            <div class="card shadow-lg border-0 rounded-4 p-5">
                <?php if (isset($data->ResponseCode) && $data->ResponseCode == "0"): ?>
                    <div class="mb-4">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <h3 class="fw-bold">Request Sent!</h3>
                    <p class="text-muted">Please check your phone and enter your M-Pesa PIN to complete the payment.</p>
                    <div class="alert alert-info small">
                        Waiting for confirmation...
                    </div>

                    <div class="mt-3">
                        <p class="small text-muted">Accidentally closed the prompt?</p>
                        <a href="payment.php?booking_id=<?php echo $booking_id; ?>&vehicle_id=<?php echo $booking['vehicle_id'] ?? ''; ?>" class="btn btn-link btn-sm text-decoration-none">Back to Payment</a>
                    </div>

                    <script>
                        // Poll the server every 3 seconds to check if the payment is complete
                        const checkStatus = setInterval(function() {
                            fetch('check-payment-status.php?booking_id=<?php echo $booking_id; ?>')
                                .then(response => response.json())
                                .then(data => {
                                    if (data.status === 'paid') {
                                        clearInterval(checkStatus);
                                        window.location.href = 'payment-success.php?booking_id=<?php echo $booking_id; ?>';
                                    } else if (data.status === 'failed' || data.status === 'cancelled') {
                                        clearInterval(checkStatus);
                                        alert('Payment failed or was cancelled.');
                                        window.location.href = 'payment-cancel.php';
                                    }
                                });
                        }, 3000);
                    </script>

                <?php else: ?>
                    <div class="mb-4">
                        <i class="bi bi-x-circle text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h3 class="fw-bold">Request Failed</h3>
                    <p class="text-muted">We couldn't initiate the M-Pesa prompt. Please try again.</p>
                    <div class="alert alert-danger small">
                        Error: <?php echo $data->errorMessage ?? 'Unknown Error'; ?>
                    </div>
                    <a href="payment.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-dark rounded-pill px-4">Try Again</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>