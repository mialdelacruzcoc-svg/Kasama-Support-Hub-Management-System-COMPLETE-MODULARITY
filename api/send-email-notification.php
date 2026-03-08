<?php
// ============================================
// REUSABLE EMAIL NOTIFICATION SYSTEM
// ============================================

require_once __DIR__ . '/email-config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

/**
 * Send a styled email notification
 * 
 * @param string $to_email Recipient email
 * @param string $to_name Recipient name
 * @param string $subject Email subject
 * @param string $heading Main heading in email
 * @param string $body_content HTML content for email body
 * @param string $button_text Optional button text
 * @param string $button_url Optional button URL
 * @return array ['success' => bool, 'message' => string]
 */
function send_notification_email($to_email, $to_name, $subject, $heading, $body_content, $button_text = '', $button_url = '') {
    
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $to_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        // Button HTML (optional)
        $button_html = '';
        if (!empty($button_text) && !empty($button_url)) {
            $button_html = "
            <div style='text-align: center; margin: 30px 0;'>
                <a href='$button_url' style='display: inline-block; padding: 14px 30px; background: #4a7c2c; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;'>$button_text</a>
            </div>";
        }

        // Email body template
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f7fa;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f5f7fa; padding: 30px 0;'>
                <tr>
                    <td align='center'>
                        <table width='600' cellpadding='0' cellspacing='0' style='background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);'>
                            <!-- Header -->
                            <tr>
                                <td style='background: linear-gradient(135deg, #4a7c2c 0%, #365c20 100%); padding: 30px; text-align: center;'>
                                    <h1 style='color: white; margin: 0; font-size: 24px;'>🌿 Kasama Support Hub</h1>
                                    <p style='color: rgba(255,255,255,0.9); margin: 8px 0 0; font-size: 14px;'>PHINMA - Cagayan de Oro College</p>
                                </td>
                            </tr>
                            
                            <!-- Content -->
                            <tr>
                                <td style='padding: 40px 30px;'>
                                    <h2 style='color: #1a4a72; margin: 0 0 20px; font-size: 22px;'>$heading</h2>
                                    <p style='color: #555; margin: 0 0 10px;'>Hello <strong>$to_name</strong>,</p>
                                    <div style='color: #555; line-height: 1.7; margin-top: 15px;'>
                                        $body_content
                                    </div>
                                    $button_html
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style='background: #f8f9fa; padding: 25px 30px; border-top: 1px solid #e9ecef;'>
                                    <p style='color: #888; font-size: 13px; margin: 0; text-align: center;'>
                                        This is an automated message from Kasama Support Hub.<br>
                                        Please do not reply directly to this email.
                                    </p>
                                    <p style='color: #aaa; font-size: 12px; margin: 15px 0 0; text-align: center;'>
                                        © " . date('Y') . " Kasama Support Hub - PHINMA COC
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";

        // Plain text alternative
        $plain_text = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body_content));
        $mail->AltBody = "Hello $to_name,\n\n$heading\n\n$plain_text\n\n" . ($button_url ? "View: $button_url\n\n" : "") . "- Kasama Support Hub Team";

        // Send
        $mail->send();
        
        return ['success' => true, 'message' => 'Email sent successfully'];

    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return ['success' => false, 'message' => 'Failed to send email: ' . $mail->ErrorInfo];
    }
}

/**
 * Pre-built email templates
 */

// Email: Coach replied to concern
function send_coach_reply_email($student_email, $student_name, $tracking_id, $subject, $coach_name, $reply_preview) {
    $short_reply = strlen($reply_preview) > 150 ? substr($reply_preview, 0, 150) . '...' : $reply_preview;
    
    $body = "
        <p>Great news! <strong>Coach $coach_name</strong> has replied to your concern.</p>
        
        <div style='background: #f0f7ec; padding: 20px; border-radius: 8px; border-left: 4px solid #4a7c2c; margin: 20px 0;'>
            <p style='margin: 0 0 8px; font-size: 12px; color: #666; text-transform: uppercase;'>Concern: $tracking_id</p>
            <p style='margin: 0 0 12px; font-weight: 600; color: #333;'>$subject</p>
            <p style='margin: 0; color: #555; font-style: italic;'>\"$short_reply\"</p>
        </div>
        
        <p>Log in to your account to view the full response and continue the conversation.</p>
    ";
    
    return send_notification_email(
        $student_email,
        $student_name,
        "Coach Replied to Your Concern - $tracking_id",
        "💬 New Reply from Coach",
        $body,
        "View Concern",
        SYSTEM_URL . "/concern-details.php?id=$tracking_id"
    );
}

// Email: Concern status changed
function send_status_change_email($student_email, $student_name, $tracking_id, $subject, $new_status) {
    $status_colors = [
        'Pending' => '#ef6c00',
        'In Progress' => '#1565c0',
        'Resolved' => '#2e7d32'
    ];
    $status_icons = [
        'Pending' => '⏳',
        'In Progress' => '🔄',
        'Resolved' => '✅'
    ];
    $status_messages = [
        'Pending' => 'Your concern is pending review.',
        'In Progress' => 'Your concern is now being actively reviewed by our coach.',
        'Resolved' => 'Great news! Your concern has been resolved. If you need further assistance, you can reply to reopen it.'
    ];
    
    $color = $status_colors[$new_status] ?? '#666';
    $icon = $status_icons[$new_status] ?? '📋';
    $message = $status_messages[$new_status] ?? "Your concern status has been updated to: $new_status";
    
    $body = "
        <p>The status of your concern has been updated.</p>
        
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;'>
            <p style='margin: 0 0 8px; font-size: 12px; color: #666;'>Concern: <strong>$tracking_id</strong></p>
            <p style='margin: 0 0 15px; color: #333;'>$subject</p>
            <div style='display: inline-block; padding: 10px 25px; background: $color; color: white; border-radius: 25px; font-weight: 600; font-size: 16px;'>
                $icon $new_status
            </div>
        </div>
        
        <p>$message</p>
    ";
    
    return send_notification_email(
        $student_email,
        $student_name,
        "Concern Status Updated to $new_status - $tracking_id",
        "$icon Status Update",
        $body,
        "View Concern",
        SYSTEM_URL . "/concern-details.php?id=$tracking_id"
    );
}

// Email: Appointment confirmed
function send_appointment_confirmed_email($student_email, $student_name, $date, $time, $coach_name) {
    $formatted_date = date('l, F d, Y', strtotime($date));
    
    $body = "
        <p>Your appointment with <strong>Coach $coach_name</strong> has been confirmed!</p>
        
        <div style='background: #e8f5e9; padding: 25px; border-radius: 8px; margin: 20px 0; text-align: center;'>
            <p style='margin: 0 0 5px; font-size: 14px; color: #2e7d32;'>✅ CONFIRMED</p>
            <p style='margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #333;'>$formatted_date</p>
            <p style='margin: 0; font-size: 24px; font-weight: 700; color: #4a7c2c;'>$time</p>
        </div>
        
        <p><strong>📍 Location:</strong> Kasama Support Hub Office</p>
        <p><strong>👤 Coach:</strong> $coach_name</p>
        
        <p style='margin-top: 20px;'>Please arrive 5-10 minutes before your scheduled time. If you need to reschedule, please contact us as soon as possible.</p>
    ";
    
    return send_notification_email(
        $student_email,
        $student_name,
        "Appointment Confirmed - $formatted_date at $time",
        "✅ Appointment Confirmed!",
        $body,
        "View Dashboard",
        SYSTEM_URL . "/student-dashboard.php"
    );
}

// Email: Appointment reschedule requested
function send_appointment_reschedule_email($student_email, $student_name, $date, $time, $coach_name, $reason = '') {
    $formatted_date = date('l, F d, Y', strtotime($date));
    
    $reason_html = '';
    if (!empty($reason)) {
        $reason_html = "
        <div style='background: #fff3e0; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ef6c00;'>
            <p style='margin: 0 0 5px; font-size: 12px; color: #666;'>Message from Coach:</p>
            <p style='margin: 0; color: #333; font-style: italic;'>\"$reason\"</p>
        </div>";
    }
    
    $body = "
        <p><strong>Coach $coach_name</strong> has requested to reschedule your appointment.</p>
        
        <div style='background: #fff3e0; padding: 25px; border-radius: 8px; margin: 20px 0; text-align: center;'>
            <p style='margin: 0 0 5px; font-size: 14px; color: #ef6c00;'>📅 RESCHEDULE REQUESTED</p>
            <p style='margin: 0 0 8px; font-size: 18px; color: #333;'>Original: $formatted_date</p>
            <p style='margin: 0; font-size: 20px; font-weight: 600; color: #ef6c00;'>$time</p>
        </div>
        
        $reason_html
        
        <p>Please log in to your account to view details and coordinate a new schedule.</p>
    ";
    
    return send_notification_email(
        $student_email,
        $student_name,
        "Appointment Reschedule Requested - $formatted_date",
        "📅 Reschedule Requested",
        $body,
        "View Dashboard",
        SYSTEM_URL . "/student-dashboard.php"
    );
}

// Email: Appointment completed
function send_appointment_completed_email($student_email, $student_name, $date, $time) {
    $formatted_date = date('F d, Y', strtotime($date));
    
    $body = "
        <p>Your appointment on <strong>$formatted_date at $time</strong> has been marked as completed.</p>
        
        <div style='background: #e3f2fd; padding: 25px; border-radius: 8px; margin: 20px 0; text-align: center;'>
            <p style='font-size: 48px; margin: 0;'>✔️</p>
            <p style='margin: 10px 0 0; font-size: 18px; font-weight: 600; color: #1565c0;'>Appointment Completed</p>
        </div>
        
        <p>Thank you for meeting with us! If you have any follow-up concerns or need additional support, feel free to submit a new concern or book another appointment.</p>
        
        <p>We're always here to help! 💚</p>
    ";
    
    return send_notification_email(
        $student_email,
        $student_name,
        "Appointment Completed - Thank You!",
        "✔️ Appointment Completed",
        $body,
        "Book Another Appointment",
        SYSTEM_URL . "/book-appointment.php"
    );
}

// Email: New FAQ added
function send_new_faq_email($student_email, $student_name, $category, $question) {
    $short_question = strlen($question) > 100 ? substr($question, 0, 100) . '...' : $question;
    
    $body = "
        <p>A new FAQ has been added that might help answer your questions!</p>
        
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <p style='margin: 0 0 8px; font-size: 12px; color: #4a7c2c; text-transform: uppercase; font-weight: 600;'>📚 $category</p>
            <p style='margin: 0; font-size: 16px; color: #333; font-weight: 500;'>$short_question</p>
        </div>
        
        <p>Check out our FAQ page to find answers to common questions and get quick help!</p>
    ";
    
    return send_notification_email(
        $student_email,
        $student_name,
        "New FAQ Added - $category",
        "📚 New FAQ Available",
        $body,
        "View FAQs",
        SYSTEM_URL . "/faq.php"
    );
}

?>