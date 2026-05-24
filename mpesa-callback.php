<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$callbackJSON = file_get_contents('php://input');
require_once 'includes/mailer.php';
$data = json_decode($callbackJSON, true);

// Save raw logs (important for debugging)
file_put_contents(__DIR__ . "/logs/mpesa_raw.log", $callbackJSON . PHP_EOL, FILE_APPEND);

// Check if valid callback
if (!isset($data['Body']['stkCallback'])) {
    exit;
}

$stk = $data['Body']['stkCallback'];

$merchant = $stk['MerchantRequestID'];
$checkout = $stk['CheckoutRequestID'];
$resultCode = $stk['ResultCode'];
$resultDesc = $stk['ResultDesc'];

$amount = 0;
$receipt = "";
$phone = "";
$metadata = $stk['CallbackMetadata']['Item'] ?? [];

foreach ($metadata as $item) {
    if ($item['Name'] == 'Amount') $amount = $item['Value'];
    if ($item['Name'] == 'MpesaReceiptNumber') $receipt = $item['Value'];
    if ($item['Name'] == 'PhoneNumber') $phone = $item['Value'];
}

$conn = new mysqli("localhost", "hiresphere_user", "8964", "hiresphere");

if ($conn->connect_error) {
    die("DB Connection failed");
}

$query = "SELECT b.*, v.vehicle_name 
          FROM bookings b 
          JOIN vehicles v ON b.vehicle_id = v.id 
          WHERE b.checkout_request_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $checkout);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($res && ($res['payment_status'] == 'paid' || $res['payment_status'] == 'failed')) {
    exit; // already processed
}

/*
| UPDATE BOOKING STATUS FIRST
|--------------------------------------------------------------------------
*/

if ($resultCode == 0) {
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET payment_status = 'paid',
            status = 'confirmed',
            mpesa_receipt = ?,
            paid_amount = ?
        WHERE checkout_request_id = ?
    ");

    $stmt->bind_param("sds", $receipt, $amount, $checkout);
    $stmt->execute();
    $stmt->close();

    // SEND CONFIRMATION EMAIL
    $to = $res['customer_email'];
    $subject = "Booking Confirmed - " . $res['vehicle_name'];
    
    $formatted_start = date('M d, Y', strtotime($res['start_date']));
    $formatted_end = date('M d, Y', strtotime($res['end_date']));
    
    $body = "
        <h2>Booking Confirmed! 🎉</h2>
        <p>Hello {$res['customer_name']}, your payment for <strong>{$res['vehicle_name']}</strong> was successful.</p>
        <p><strong>Pick-up Date:</strong> {$formatted_start}</p>
        <p><strong>Return Date:</strong> {$formatted_end}</p>
        <p><strong>Pick-up Location:</strong> Nairobi Central Office (or contact your host for specific details).</p>
        <p><strong>Receipt Number:</strong> {$receipt}</p>
        <br>
        <p>Thank you for choosing HireSphere!</p>
    ";

    sendEmail($to, $subject, $body);
} else {
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET payment_status = 'failed'
        WHERE checkout_request_id = ?
    ");

    $stmt->bind_param("s", $checkout);
    $stmt->execute();
    $stmt->close();
}

// Only log to mpesa_payments if it was a success, 
// or ensure fields allow nulls for cancellations

$stmt = $conn->prepare("
    INSERT INTO mpesa_payments (
        checkout_request_id,
        merchant_request_id,
        receipt_number,
        phone,
        amount,
        result_code,
        result_desc
    ) VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssssdis",
    $checkout,
    $merchant,
    $receipt,
    $phone,
    $amount,
    $resultCode,
    $resultDesc
);

$stmt->execute();
$stmt->close();
$conn->close();

echo "OK";