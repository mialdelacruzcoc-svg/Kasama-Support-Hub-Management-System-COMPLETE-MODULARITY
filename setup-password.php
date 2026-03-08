<?php
session_start();
// Siguruha nga naay email sa session para dili ma-bypass ang security
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set Password - Kasama Support Hub</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/setup-password-styles.css">
    <link rel="stylesheet" href="css/dark-mode.css">
    <script src="js/dark-mode.js"></script>
</head>
<body style="display: flex; align-items: center; min-height: 100vh; background: #f0f2f5;">
    <div class="password-container">
        <div class="password-header">
            <h1>Create Password</h1>
            <p>Set a strong password for your account</p>
        </div>
        <div id="alertBox"></div>
        <form id="passwordForm">
            <div class="input-group">
                <label>New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" required minlength="8" placeholder="Minimum 8 characters">
                    <button type="button" class="toggle-password" onclick="togglePass('password', this)" title="Show password">👁</button>
                </div>
            </div>
            <div class="input-group">
                <label>Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirmPassword" required placeholder="Repeat your password">
                    <button type="button" class="toggle-password" onclick="togglePass('confirmPassword', this)" title="Show password">👁</button>
                </div>
            </div>
            <button type="submit" class="btn-finish" id="finishBtn">Complete Registration</button>
        </form>
    </div>


    <script src="js/setup-password.js"></script>
</body>
</html>