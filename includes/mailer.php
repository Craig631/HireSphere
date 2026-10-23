<?php

use PHPMailer\PHPMailer\PHPMailer;

/* Resolve paths from this file so callbacks and terminal commands both work. */
require_once dirname(__DIR__) . '/vendor/autoload.php';

/* Load email settings without overwriting environment variables already loaded. */
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

/* Build one SMTP configuration shared by normal emails and attachments. */
function hsCreateMailer(): PHPMailer
{
    $username = trim($_ENV['MAIL_USERNAME'] ?? '');
    $password = $_ENV['MAIL_PASSWORD'] ?? '';

    if ($username === '' || $password === '') {
        throw new RuntimeException('Email configuration is incomplete.');
    }

    $mail = new PHPMailer(true);

    /* Authenticate to Gmail using the app password stored in .env. */
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $username;
    $mail->Password = $password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    /* Limit connection delays and support names containing non-English characters. */
    $mail->Timeout = 15;
    $mail->CharSet = 'UTF-8';

    /* Both email functions now use the same sender identity. */
    $mail->setFrom($username, 'HireSphere');
    $mail->isHTML(true);

    return $mail;
}

/* Deliver an HTML email and optionally attach a generated document. */
function hsDeliverEmail(
    string $to,
    string $subject,
    string $body,
    ?string $attachmentPath = null
): bool {
    try {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid recipient address.');
        }

        $mail = hsCreateMailer();

        /* Embed the logo so email clients do not need access to the local website. */
        $logoPath = dirname(__DIR__) . '/assets/images/hiresphere-logo.png';

        if (!is_file($logoPath) || !is_readable($logoPath)) {
            throw new RuntimeException('The company logo file is missing or unreadable.');
        }

        /* The Content-ID matches the cid: address used by the email template. */
        $mail->addEmbeddedImage(
            $logoPath,
            'hiresphere-company-logo',
            'hiresphere-logo.png',
            'base64',
            'image/png'
        );

        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;

        /* Provide readable plain text for email clients that disable HTML. */
        $plainText = preg_replace(
            '/<br\s*\/?>|<\/(?:p|div|h[1-6]|li|tr)>/i',
            "\n",
            $body
        );

        $mail->AltBody = trim(html_entity_decode(
            strip_tags($plainText),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        ));

        /* Report a missing invoice instead of silently sending without it. */
        if ($attachmentPath !== null) {
            if (!is_file($attachmentPath) || !is_readable($attachmentPath)) {
                throw new RuntimeException('The email attachment is unavailable.');
            }

            $mail->addAttachment($attachmentPath);
        }

        return $mail->send();
    } catch (Throwable $exception) {
        /* Log failures without interrupting registration or payment processing. */
        error_log('HireSphere email failed: ' . $exception->getMessage());
        return false;
    }
}

/* Preserve the function names already used by your payment handlers. */
function sendEmail($to, $subject, $body): bool
{
    return hsDeliverEmail($to, $subject, $body);
}

/* Attachments use exactly the same credentials as ordinary notifications. */
function sendEmailWithAttachment(
    $to,
    $subject,
    $body,
    $attachmentPath
): bool {
    return hsDeliverEmail($to, $subject, $body, $attachmentPath);
}