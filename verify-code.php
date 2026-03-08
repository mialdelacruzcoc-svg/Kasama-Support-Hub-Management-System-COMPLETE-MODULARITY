<?php
session_start();
// Opsyonal: I-check kung naay registration_email sa session/sessionStorage 
// para dili ma-access sa bisan kinsa ang page nga walay request.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - Kasama Support Hub</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/verify-code-styles.css">
    <link rel="stylesheet" href="css/dark-mode.css">
    <script src="js/dark-mode.js"></script>
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f0f2f5; padding: 20px;">

    <div class="verify-container">
        <div class="verify-header">
            <img src="images/phinma-logo.png" alt="Logo" style="width: 60px; margin-bottom: 15px;">
            <h1>Verify Your Email</h1>
            <p>We've sent a 6-digit code to: <br>
               <span id="displayEmail" class="email-display">---</span>
            </p>
        </div>

        <div id="alertBox"></div>

        <form id="verifyForm">
            <div class="otp-container">
                <input type="text" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
            </div>

            <button type="submit" class="btn-verify" id="verifyBtn">Verify Account</button>
        </form>

        <p class="resend-text">
            Didn't receive the code? <br>
            <span id="resendTimer">Resend in 60s</span>
            <a id="resendLink" class="resend-link disabled" style="display:none">Resend Code</a>
        </p>

        <div style="text-align: center; margin-top: 20px;">
            <a href="register.php" style="color: #666; font-size: 13px; text-decoration: none;">← Back to Registration</a>
        </div>
    </div>


    <script src="js/verify-code.js"></script>
</body>
</html>