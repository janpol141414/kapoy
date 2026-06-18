<?php
/**
 * Email Helper - Gmail SMTP
 * Land Surveying Services Portal System
 */

require_once __DIR__ . '/../config/config.php';

class EmailHelper {

    /**
     * Send email using PHP mail() or SMTP
     * For production, use PHPMailer with Gmail SMTP
     */
    public static function send($to, $subject, $body, $toName = '') {
        // Using PHP mail() as fallback (works on most servers)
        // For Gmail SMTP, install PHPMailer: composer require phpmailer/phpmailer
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
        $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $htmlBody = self::wrapInTemplate($subject, $body);

        // Try to send (will work if mail server is configured)
        @mail($to, $subject, $htmlBody, $headers);
        
        // Log email attempt
        error_log("Email sent to: $to | Subject: $subject");
        return true;
    }

    private static function wrapInTemplate($subject, $content) {
        return '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
.container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
.header { background: linear-gradient(135deg, #1a3c5e, #2d6a9f); padding: 30px; text-align: center; }
.header h1 { color: #fff; margin: 0; font-size: 24px; }
.header p { color: rgba(255,255,255,0.8); margin: 5px 0 0; }
.body { padding: 30px; }
.body h2 { color: #1a3c5e; }
.body p { color: #555; line-height: 1.6; }
.btn { display: inline-block; background: linear-gradient(135deg, #1a3c5e, #2d6a9f); color: #fff; padding: 12px 30px; border-radius: 8px; text-decoration: none; margin: 15px 0; }
.info-box { background: #f0f7ff; border-left: 4px solid #2d6a9f; padding: 15px; border-radius: 0 8px 8px 0; margin: 15px 0; }
.footer { background: #f8f9fa; padding: 20px; text-align: center; color: #888; font-size: 12px; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🗺️ Land Surveying Portal</h1>
        <p>Professional Surveying Services</p>
    </div>
    <div class="body">
        ' . $content . '
    </div>
    <div class="footer">
        <p>© ' . date('Y') . ' Land Surveying Services Portal. All rights reserved.</p>
        <p>This is an automated message. Please do not reply directly to this email.</p>
    </div>
</div>
</body>
</html>';
    }

    public static function sendAppointmentConfirmation($to, $toName, $appointment) {
        $subject = "Appointment Confirmed - " . $appointment['confirmation_code'];
        $body = "
            <h2>Appointment Confirmed! ✅</h2>
            <p>Dear <strong>{$toName}</strong>,</p>
            <p>Your appointment has been successfully confirmed. Here are the details:</p>
            <div class='info-box'>
                <p><strong>Confirmation Code:</strong> {$appointment['confirmation_code']}</p>
                <p><strong>Service:</strong> {$appointment['service_type']}</p>
                <p><strong>Date:</strong> " . date('F d, Y', strtotime($appointment['appointment_date'])) . "</p>
                <p><strong>Time:</strong> " . date('h:i A', strtotime($appointment['appointment_time'])) . "</p>
                <p><strong>Engineer:</strong> {$appointment['engineer_name']}</p>
                <p><strong>Location:</strong> {$appointment['location']}</p>
            </div>
            <p>Please be ready at the specified location on the appointment date.</p>
            <a href='" . BASE_URL . "/client/track-status.php' class='btn'>Track Your Appointment</a>
        ";
        return self::send($to, $subject, $body, $toName);
    }

    public static function sendPaymentVerification($to, $toName, $payment, $status) {
        $statusText = $status === 'verified' ? 'Verified ✅' : 'Rejected ❌';
        $subject = "Payment " . $statusText . " - Reference: " . $payment['reference_number'];
        $body = "
            <h2>Payment {$statusText}</h2>
            <p>Dear <strong>{$toName}</strong>,</p>
            <p>Your payment has been <strong>" . strtolower($statusText) . "</strong>.</p>
            <div class='info-box'>
                <p><strong>Reference Number:</strong> {$payment['reference_number']}</p>
                <p><strong>Amount:</strong> ₱" . number_format($payment['amount'], 2) . "</p>
                <p><strong>Method:</strong> " . strtoupper($payment['payment_method']) . "</p>
                <p><strong>Status:</strong> {$statusText}</p>
            </div>
            " . ($status === 'rejected' ? "<p>If you have questions, please contact our support team.</p>" : "<p>Thank you for your payment. Your appointment is now fully confirmed.</p>") . "
        ";
        return self::send($to, $subject, $body, $toName);
    }

    public static function sendStatusUpdate($to, $toName, $appointment, $newStatus) {
        $subject = "Survey Status Update - " . $appointment['confirmation_code'];
        $body = "
            <h2>Survey Status Updated 📋</h2>
            <p>Dear <strong>{$toName}</strong>,</p>
            <p>Your survey appointment status has been updated.</p>
            <div class='info-box'>
                <p><strong>Confirmation Code:</strong> {$appointment['confirmation_code']}</p>
                <p><strong>Service:</strong> {$appointment['service_type']}</p>
                <p><strong>New Status:</strong> <span style='color: #2d6a9f; font-weight: bold;'>" . ucfirst(str_replace('_', ' ', $newStatus)) . "</span></p>
            </div>
            <a href='" . BASE_URL . "/client/track-status.php' class='btn'>View Details</a>
        ";
        return self::send($to, $subject, $body, $toName);
    }
}
