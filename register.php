<?php
session_start();
// Redirect kung naka-login na
if (isset($_SESSION['user_id'])) {
    $redirect = ($_SESSION['role'] === 'coach') ? 'pages/coach/dashboard.php' : 'pages/student/dashboard.php';
    header('Location: ' . $redirect);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Kasama Support Hub</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/register-styles.css">
    <link rel="stylesheet" href="css/dark-mode.css">
    <script src="js/dark-mode.js"></script>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <img src="images/phinma-logo.png" alt="PHINMA Logo">
            <h1>Student Access</h1>
            <p>Step 1: Verify your PHINMA Email</p>
        </div>

        <div id="alertBox"></div>

        <form id="registerForm">
            <div class="input-group">
                <label>Full Name *</label>
                <input type="text" id="fullname" placeholder="Juan Dela Cruz" required>
            </div>

            <div class="input-group">
                <label>COC Email Address *</label>
                <input type="email" id="email" placeholder="name.coc@phinmaed.com" required>
            </div>

            <div class="input-group">
    <label>Student ID * (e.g., 03-2223-012345)</label>
    <input type="text" id="studentId" placeholder="00-0000-000000" required maxlength="15">
</div>

<div class="input-group">
    <label>Year Level *</label>
    <select id="yearLevel" required style="width: 100%; padding: 12px 15px; border: 1.5px solid #e0e0e0; border-radius: 8px; box-sizing: border-box; font-size: 14px; background: white; color: #333;">
        <option value="" disabled selected>-- Select Year Level --</option>
        <option value="1st Year">1st Year</option>
        <option value="2nd Year">2nd Year</option>
        <option value="3rd Year">3rd Year</option>
        <option value="4th Year">4th Year</option>
    </select>
</div>

<button type="submit" class="btn-register" id="submitBtn">Get Verification Code 📧</button>
        </form>

        <div class="register-footer">
            Already have an account? <a href="index.php">Sign In</a>
        </div>
    </div>


    <script src="js/register.js"></script>
</body>
</html>