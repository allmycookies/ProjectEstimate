<?php
// includes/mailer.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
require __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';

function sendMail($to, $subject, $body, $attachmentPath = null) {
    global $conn;

    // Settings laden
    $s = [];
    $res = $conn->query("SELECT * FROM settings");
    while($row = $res->fetch_assoc()) $s[$row['setting_key']] = $row['setting_value'];

    if (empty($s['smtp_host'])) return false; // Nicht konfiguriert

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $s['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $s['smtp_user'];
        $mail->Password   = $s['smtp_pass'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $s['smtp_port'];
        $mail->CharSet    = 'UTF-8';

        // Recipients
        $mail->setFrom($s['sender_email'], $s['sender_name']);
        $mail->addAddress($to);

        // Attachments
        if ($attachmentPath && file_exists($attachmentPath)) {
            $mail->addAttachment($attachmentPath);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Logging könnte hier hin
        return false;
    }
}
?>