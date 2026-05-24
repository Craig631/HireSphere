<?php

require_once 'vendor/autoload.php';

use Dompdf\Dompdf;

function generateInvoicePDF($payment, $user, $vehicle) {

    $dompdf = new Dompdf();

    $html = "
        <h1>HireSphere Invoice</h1>
        <hr>

        <p><strong>Customer:</strong> {$user['name']}</p>
        <p><strong>Email:</strong> {$user['email']}</p>

        <p><strong>Vehicle:</strong> {$vehicle['vehicle_name']}</p>
        <p><strong>Amount:</strong> KES {$payment['amount']}</p>
        <p><strong>Method:</strong> {$payment['method']}</p>
        <p><strong>Status:</strong> {$payment['status']}</p>
        <p><strong>Date:</strong> {$payment['created_at']}</p>

        <hr>
        <p>Thank you for using HireSphere 🚗</p>
    ";

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filePath = "invoices/invoice_" . $payment['id'] . ".pdf";

    file_put_contents($filePath, $dompdf->output());

    return $filePath;
}