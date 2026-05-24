<?php
require_once "auth/guard.php";
require_once "config/stripe.php";
include "config/database.php";

// Capture data from the POST request sent by payment.php
$user_id = $_SESSION['user_id'];
$vehicle_id = $_POST['vehicle_id'] ?? null;
$vehicle_name = $_POST['vehicle_name'] ?? 'Vehicle Rental';
$amount = $_POST['amount'] ?? 0;

// Basic validation
if (!$vehicle_id || $amount <= 0) {
    die("Invalid payment details provided.");
}

// Create a pending booking record using the session user_id
$status = 'pending';
$booking_stmt = mysqli_prepare($conn, "INSERT INTO bookings (vehicle_id, user_id, status) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($booking_stmt, "iis", $vehicle_id, $user_id, $status);
mysqli_stmt_execute($booking_stmt);
$booking_id = mysqli_insert_id($conn);

// Create the Stripe Checkout Session
$session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],

    'line_items' => [[
        'price_data' => [
            'currency' => 'kes',
            'product_data' => [
                'name' => $vehicle_name,
            ],
            'unit_amount' => $amount * 100,
        ],
        'quantity' => 1,
    ]],

    'mode' => 'payment',

    // 🔥 IMPORTANT PART
    'metadata' => [
        'user_id' => $user_id,
        'vehicle_id' => $vehicle_id,
        'booking_id' => $booking_id
    ],

    'success_url' => 'http://localhost/hiresphere/payment-success.php?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => 'http://localhost/hiresphere/payment-cancel.php',
]);

// Store Stripe session ID during payment creation
$stripe_session_id = $session->id;
$pay_stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, vehicle_id, amount, method, status, stripe_session_id) VALUES (?, ?, ?, 'stripe', 'pending', ?)");
mysqli_stmt_bind_param($pay_stmt, "iids", $user_id, $vehicle_id, $amount, $stripe_session_id);
mysqli_stmt_execute($pay_stmt);

// Redirect the user to the Stripe Checkout page
header("HTTP/1.1 303 See Other");
header("Location: " . $session->url);
exit();