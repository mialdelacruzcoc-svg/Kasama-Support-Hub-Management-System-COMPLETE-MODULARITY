<?php
// ============================================
// SEND VERIFICATION CODE API
// ============================================
date_default_timezone_set('Asia/Manila');

require_once 'config.php';
require_once 'email-config.php';

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/phpmailer/phpmailer/src/Exception.php';
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(false, 'Invalid request method');
}

// Get input
$email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
$student_id = isset($_POST['student_id']) ? sanitize_input($_POST['student_id']) : '';

// Validate inputs
if (empty($email) || empty($student_id)) {
    send_json_response(false, 'Email and Student ID are required');
}

// Validate email format (must be COC email)
if (!preg_match('/^[a-z]+\.[a-z]+\.coc@phinmaed\.com$/i', $email)) {
    send_json_response(false, 'Please use your official COC email address');
}

// Validate Student ID format (XX-XXXX-XXXXX or XX-XXXX-XXXXXX)
if (!preg_match('/^\d{2}-\d{4}-\d{5,6}$/', $student_id)) {
    send_json_response(false, 'Invalid Student ID format. Use: 03-2223-01234 or 03-2223-012345');
}

// Get user IP
$ip_address = $_SERVER['REMOTE_ADDR'];

// === RATE LIMITING ===

// Check: Max 3 code requests per hour per email
$sql = "SELECT COUNT(*) as count FROM registration_attempts 
        WHERE email = '$email' 
        AND attempt_type = 'code_request' 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

if ($row['count'] >= MAX_CODE_REQUESTS_PER_HOUR) {
    send_json_response(false, 'Too many requests. Please try again in 1 hour.');
}

// Check: User already registered?
$sql = "SELECT id FROM users WHERE email = '$email' OR student_id = '$student_id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    send_json_response(false, 'This email or Student ID is already registered.');
}

// === GENERATE CODE ===

// Generate 6-digit code
$code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

// Calculate expiry (15 minutes from now)
$expires_at = date('Y-m-d H:i:s', strtotime('+' . VERIFY_CODE_EXPIRY_MINUTES . ' minutes'));

// Delete old codes for this email
$sql = "DELETE FROM verification_codes WHERE email = '$email'";
mysqli_query($conn, $sql);

// Insert new code
$sql = "INSERT INTO verification_codes (email, code, student_id, expires_at, ip_address) 
        VALUES ('$email', '$code', '$student_id', '$expires_at', '$ip_address')";

if (!mysqli_query($conn, $sql)) {
    send_json_response(false, 'Database error: ' . mysqli_error($conn));
}

// Log attempt
$sql = "INSERT INTO registration_attempts (email, ip_address, attempt_type) 
        VALUES ('$email', '$ip_address', 'code_request')";
mysqli_query($conn, $sql);

// === SEND EMAIL ===

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
    $mail->addAddress($email);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Kasama Support Hub - Verification Code';
    
    // Email body
    $mail->Body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #4a7c2c; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; }
            .code { background: white; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 10px; color: #4a7c2c; border: 2px dashed #4a7c2c; margin: 20px 0; }
            .footer { text-align: center; color: #666; font-size: 12px; padding: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Kasama Support Hub</h1>
            </div>
            <div class='content'>
                <h2>Email Verification Code</h2>
                <p>Hello,</p>
                <p>Your verification code is:</p>
                <div class='code'>$code</div>
                <p><strong>This code expires in " . VERIFY_CODE_EXPIRY_MINUTES . " minutes.</strong></p>
                <p>If you didn't request this, please ignore this email.</p>
            </div>
            <div class='footer'>
                <p>- Kasama Support Hub Team</p>
                <p>Cagayan de Oro College - PHINMA Education</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Plain text alternative
    $mail->AltBody = "Your Kasama Support Hub verification code is: $code\n\nThis code expires in " . VERIFY_CODE_EXPIRY_MINUTES . " minutes.";

    // Send
    $mail->send();

    send_json_response(true, 'Verification code sent successfully', [
        'email' => $email,
        'expires_in_minutes' => VERIFY_CODE_EXPIRY_MINUTES
    ]);

} catch (Exception $e) {
    send_json_response(false, 'Failed to send email: ' . $mail->ErrorInfo);
}

mysqli_close($conn);
?>