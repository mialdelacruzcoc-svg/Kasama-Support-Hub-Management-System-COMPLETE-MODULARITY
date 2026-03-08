<?php
// reset-password.php
require_once 'api/config.php';

$token = $_GET["token"] ?? "";
$token_hash = hash("sha256", $token);

// I-verify ang token
$sql = "SELECT * FROM users WHERE reset_token_hash = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || strtotime($user["reset_token_expires_at"]) <= time()) {
    die("Token invalid or expired.");
}

// Kon naay nag-submit sa bag-ong password
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // I-update ang password ug i-delete ang token  
    $update_sql = "UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_password, $user["id"]);
    $update_stmt->execute();

    echo "<script>alert('Password updated!'); window.location.href='index.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Kasama Hub</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/reset-password-styles.css">
    <link rel="stylesheet" href="css/dark-mode.css">
    <script src="js/dark-mode.js"></script>
</head>
<body class="login-wrapper">
    <div class="login-container" style="max-width: 450px; padding: 30px;">
        <form method="POST">
            <h2>Set New Password</h2>
            <div class="input-group">
                <label>New Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="resetPassword" required minlength="6">
                    <button type="button" class="toggle-password" onclick="togglePass('resetPassword', this)" title="Show password">👁</button>
                </div>
            </div>
            <button type="submit" class="btn-signin">Update Password</button>
        </form>
    </div>

    <script src="js/reset-password.js"></script>
</body>
</html>