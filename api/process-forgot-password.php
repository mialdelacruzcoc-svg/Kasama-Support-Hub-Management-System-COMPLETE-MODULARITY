<?php
require_once 'config.php';

// Atong i-hide ang technical errors sa user para limpyo tan-awon
error_reporting(0); 

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Kasama Support Hub</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { background: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; font-family: sans-serif; }
        .result-card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 450px; text-align: center; }
        .icon { font-size: 50px; margin-bottom: 20px; }
        .status-header { color: #4a7c2c; font-weight: bold; font-size: 22px; margin-bottom: 15px; }
        .error-header { color: #d93025; font-weight: bold; font-size: 22px; margin-bottom: 15px; }
        .instruction-text { color: #666; line-height: 1.6; margin-bottom: 25px; }
        .btn-action { display: inline-block; padding: 14px 25px; background: #4a7c2c; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: 0.3s; width: 80%; }
        .btn-action:hover { background: #3d6824; }
    </style>
</head>
<body>
    <div class="result-card">';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"] ?? '';

    if (empty($email)) {
        echo '<div class="icon">⚠️</div>';
        echo '<div class="error-header">Email Required</div>';
        echo '<p class="instruction-text">Please enter your PHINMA email address to proceed.</p>';
        echo '<a href="../forgot-password.php" class="btn-action" style="background:#666;">Go Back</a>';
    } else {
        $token = bin2hex(random_bytes(16));
        $token_hash = hash("sha256", $token);
        $expiry = date("Y-m-d H:i:s", time() + 60 * 30);

        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $sql = "UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $token_hash, $expiry, $email);
            
            if ($stmt->execute()) {
                echo '<div class="icon">📧</div>';
                echo '<div class="status-header">Link Generated</div>';
                echo '<p class="instruction-text">You can now create a new password. Just press the button below for setting "Valid Password".</p>';
                // Note: For actual deployment, this link goes to email. For your testing, it stays on screen.
                echo '<a href="../reset-password.php?token=' . $token . '" class="btn-action">Set New Password Now</a>';
            } else {
                echo '<div class="error-header">System Busy</div>';
                echo '<p class="instruction-text">Please try again later or contact support.</p>';
            }
        } else {
            echo '<div class="icon">❌</div>';
            echo '<div class="error-header">Email Not Found</div>';
            echo '<p class="instruction-text">We did not find an account for the email you entered</p>';
            echo '<a href="../forgot-password.php" class="btn-action" style="background:#666;">Try Again</a>';
        }
    }
} else {
    echo '<div class="icon">🚫</div><div class="error-header">Invalid Access</div>';
}

echo '    </div>
</body>
</html>';
?>