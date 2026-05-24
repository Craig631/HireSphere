<?php

require_once 'vendor/autoload.php';
include 'config/database.php';
require_once 'includes/mailer.php';
require_once 'includes/invoice-generator.php';

\Stripe\Stripe::setApiKey("sk_test_YOUR_SECRET_KEY");

$endpoint_secret = "whsec_4fc5e7834e8c717e8bf47ba210acf0a34d5ba3abe2aa6e0f3a9e42b342858382";

$payload = @file_get_contents("php://input");
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

try {

    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        $endpoint_secret
    );

} catch(Exception $e) {

    http_response_code(400);
    exit();
}

if ($event->type === 'checkout.session.completed') {

    $session = $event->data->object;

    $session_id = $session->id;

    $user_id = (int)($session->metadata->user_id ?? 0);
    $vehicle_id = (int)($session->metadata->vehicle_id ?? 0);
    $booking_id = (int)($session->metadata->booking_id ?? 0);

    // Update payment status
    mysqli_query($conn,
        "UPDATE payments
         SET status='success'
         WHERE stripe_session_id='$session_id'"
    );

    // Optional booking update
    mysqli_query($conn,
        "UPDATE bookings b
         JOIN payments p ON b.id = p.booking_id
         SET b.payment_status='paid',
             b.status='confirmed'
         WHERE p.stripe_session_id='$session_id'"
    );

    // Get full payment record for the invoice
    $payment_query = mysqli_query($conn, "SELECT * FROM payments WHERE stripe_session_id='$session_id' LIMIT 1");
    $payment = mysqli_fetch_assoc($payment_query);

    // Generate booking reference from the session ID
    $reference = strtoupper(substr(md5($session_id), 0, 10));

    // Fetch full vehicle data for the invoice and email
    $vehicle_query = mysqli_query($conn, "SELECT * FROM vehicles WHERE id = $vehicle_id");
    $vehicle = mysqli_fetch_assoc($vehicle_query);
    $vehicle_name = $vehicle['vehicle_name'] ?? 'Vehicle';

    // Fetch booking dates for the email body
    $booking_query = mysqli_query($conn, "SELECT * FROM bookings WHERE id = $booking_id");
    $booking_data = mysqli_fetch_assoc($booking_query);

    // Format dates for better readability
    $pickup_date_obj = new DateTime($booking_data['start_date'] ?? 'now');
    $return_date_obj = new DateTime($booking_data['end_date'] ?? 'now');
    $formatted_pickup_date = $pickup_date_obj->format('M d, Y');
    $formatted_return_date = $return_date_obj->format('M d, Y');

    // Fetch full user data for the invoice
    $user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
    $user = mysqli_fetch_assoc($user_query);

    // --- SEND EMAIL NOTIFICATIONS ---

    if ($user) {
        // Ensure invoices directory exists
        if (!is_dir('invoices')) mkdir('invoices', 0777, true);

        // Generate invoice PDF
        $pdfPath = generateInvoicePDF($payment, $user, $vehicle);

        $email = $booking_data['customer_email'] ?? $user['email'];
        $name = $user['name'];

        $body = "
        <h2>Payment Successful 🎉</h2>
        <p>Your booking is confirmed.</p>
        <p>Invoice is attached below.</p>
        ";

        // Send email with the PDF attachment
        sendEmailWithAttachment($email, "HireSphere Invoice - Booking Confirmed", $body, $pdfPath);
    }

    // 2. Host Notification
    $host_query = mysqli_query($conn, 
        "SELECT users.email FROM users 
         JOIN vehicles ON vehicles.host_id = users.id 
         WHERE vehicles.id = $vehicle_id"
    );
    $host = mysqli_fetch_assoc($host_query);

    if ($host) {
        $host_body = "
        <h3>You received a new booking on HireSphere</h3>
        <p><strong>Vehicle:</strong> $vehicle_name</p>
        <p><strong>Booking Reference:</strong> $reference</p>
        <p><strong>Rental Period:</strong> $formatted_pickup_date to $formatted_return_date</p>
        <p>Login to your dashboard to manage the booking.</p>
        ";

        sendEmail($host['email'], "New Booking Received - $vehicle_name", $host_body);
    }

    file_put_contents(
        "stripe-log.txt",
        "Payment success for session: " . $session_id . PHP_EOL,
        FILE_APPEND
    );
}

http_response_code(200);
?>