<?php
/* Escape text so names and booking details display safely inside emails. */
function hsEmailText($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/*
 * Build a reusable email layout.
 * Table layouts and inline CSS work across many email applications.
 * Pass plain text into this function; it escapes the values automatically.
 */
function hsEmailTemplate(
    string $name,
    string $title,
    string $message,
    array $details = [],
    string $note = '',
    string $accent = '#0057ff'
): string {
    /* Restrict accent colors to valid hexadecimal values. */
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) {
        $accent = '#0057ff';
    }

    $safeName = hsEmailText($name);
    $safeTitle = hsEmailText($title);
    $safeMessage = nl2br(hsEmailText($message));
    $safeNote = nl2br(hsEmailText($note));
    $year = date('Y');
    $detailRows = '';

    /* Turn booking details into neatly aligned label-and-value rows. */
    foreach ($details as $label => $value) {
        $safeLabel = hsEmailText($label);
        $safeValue = hsEmailText($value);

        $detailRows .= "
            <tr>
                <td style='padding:12px;border-bottom:1px solid #e5eaf2;
                           color:#64748b;font-size:13px;vertical-align:top;'>
                    {$safeLabel}
                </td>
                <td style='padding:12px;border-bottom:1px solid #e5eaf2;
                           color:#172033;font-size:13px;font-weight:bold;
                           text-align:right;vertical-align:top;'>
                    {$safeValue}
                </td>
            </tr>
        ";
    }

    /* Omit the details card when an email has no additional information. */
    $detailsHtml = $detailRows !== '' ? "
        <table role='presentation' width='100%' cellspacing='0' cellpadding='0'
               style='margin-top:24px;background:#f7f9fd;
                      border:1px solid #e5eaf2;border-radius:12px;'>
            {$detailRows}
        </table>
    " : '';

    /* Use a separate note area for retry instructions or the next step. */
    $noteHtml = $note !== '' ? "
        <table role='presentation' width='100%' cellspacing='0' cellpadding='0'
               style='margin-top:24px;'>
            <tr>
                <td style='padding:16px;background:#eff5ff;
                           border-left:4px solid {$accent};
                           color:#334155;font-size:13px;line-height:1.7;'>
                    {$safeNote}
                </td>
            </tr>
        </table>
    " : '';

    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>{$safeTitle}</title>
    </head>
    <body style='margin:0;padding:0;background:#edf1f7;
                 font-family:Arial,Helvetica,sans-serif;'>

        <!-- Outer table provides spacing around the centered email card. -->
        <table role='presentation' width='100%' cellspacing='0' cellpadding='0'
               style='background:#edf1f7;'>
            <tr>
                <td align='center' style='padding:32px 12px;'>

                    <table role='presentation' width='600'
                           cellspacing='0' cellpadding='0'
                           style='width:100%;max-width:600px;background:#ffffff;
                                  border:1px solid #e0e7f0;border-radius:16px;'>

                        <!-- Brand header uses the logo embedded by the shared mailer. -->
                        <tr>
                            <!-- A white header keeps the blue logo readable in the email. -->
                            <td style='padding:28px 30px;background:#ffffff;
                                       border-top:5px solid {$accent};
                                       border-bottom:1px solid #e5eaf2;
                                       border-radius:16px 16px 0 0;'>

                                <!-- The mailer embeds this image using the matching Content-ID. -->
                                <img src='cid:hiresphere-company-logo'
                                     alt='HireSphere'
                                     width='230'
                                     style='display:block;width:230px;max-width:100%;
                                            height:auto;border:0;'>

                                <p style='margin:14px 0 0;color:#64748b;
                                          font-family:Arial,Helvetica,sans-serif;
                                          font-size:11px;letter-spacing:1px;'>
                                    YOUR JOURNEY. YOUR DRIVE.
                                </p>

                            </td>
                        </tr>

                        <!-- Main content uses clear spacing and readable typography. -->
                        <tr>
                            <td style='padding:30px;'>
                                <p style='margin:0 0 12px;color:#64748b;
                                          font-size:14px;'>
                                    Hello {$safeName},
                                </p>

                                <h1 style='margin:0 0 18px;color:#172033;
                                           font-size:25px;line-height:1.3;'>
                                    {$safeTitle}
                                </h1>

                                <p style='margin:0;color:#475569;
                                          font-size:15px;line-height:1.8;'>
                                    {$safeMessage}
                                </p>

                                {$detailsHtml}
                                {$noteHtml}

                                <p style='margin:28px 0 0;color:#475569;
                                          font-size:14px;line-height:1.7;'>
                                    See you on the road,<br>
                                    <strong style='color:#172033;'>
                                        The HireSphere Team
                                    </strong>
                                </p>
                            </td>
                        </tr>

                        <!-- Footer explains why the customer received the email. -->
                        <tr>
                            <td style='padding:20px 30px;background:#f7f9fd;
                                       border-top:1px solid #e5eaf2;
                                       border-radius:0 0 16px 16px;'>
                                <p style='margin:0;color:#64748b;font-size:11px;
                                          line-height:1.8;text-align:center;'>
                                    This is a service notification about your
                                    HireSphere account or booking.<br>
                                    &copy; {$year} HireSphere.
                                </p>
                            </td>
                        </tr>

                    </table>

                </td>
            </tr>
        </table>
    </body>
    </html>
    ";
}