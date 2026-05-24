<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendEmail($to, $subject, $body) {

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['MAIL_USERNAME'];
        $mail->Password = $_ENV['MAIL_PASSWORD'];

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($_ENV['MAIL_USERNAME'], 'HireSphere');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();

    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
    }
}

function sendEmailWithAttachment($to, $subject, $body, $attachmentPath) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'yourgmail@gmail.com';
        $mail->Password = 'svhm jfgm buqu jogx';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('yourgmail@gmail.com', 'HireSphere');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        // Attach the generated file
        if (file_exists($attachmentPath)) {
            $mail->addAttachment($attachmentPath);
        }

        $mail->send();
    } catch (Exception $e) {
        error_log("Email with attachment failed: " . $mail->ErrorInfo);
    }
}