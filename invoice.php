<?php

require 'vendor/autoload.php';

use Dompdf\Dompdf;

include 'config/database.php';

$payment_id = (int)$_GET['payment_id'];

$query = mysqli_query($conn,
    "SELECT payments.*, vehicles.vehicle_name
     FROM payments
     JOIN vehicles ON payments.vehicle_id = vehicles.id
     WHERE payments.id = {$payment_id}
     LIMIT 1"
);

$payment = mysqli_fetch_assoc($query);

$html = '
<h1>HireSphere Invoice</h1>

<hr>

<h3>Payment Details</h3>

<p><strong>Vehicle:</strong> '.$payment['vehicle_name'].'</p>

<p><strong>Amount:</strong> KES '.$payment['amount'].'</p>

<p><strong>Method:</strong> '.strtoupper($payment['method']).'</p>

<p><strong>Status:</strong> '.$payment['status'].'</p>

<p><strong>Date:</strong> '.$payment['created_at'].'</p>

<hr>

<p>Thank you for using HireSphere.</p>
';

$dompdf = new Dompdf();

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

$dompdf->stream("invoice.pdf");
?>