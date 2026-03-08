<?php
// ============================================
// VERIFY OTP API (WITH TIMEZONE FIX)
// ============================================

require_once 'config.php';

// STEP 1: Siguruha nga pareho ang timezone sa PHP
date_default_timezone_set('Asia/Manila');
$current_time = date('Y-m-d H:i:s'); 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
$otp = isset($_POST['otp']) ? mysqli_real_escape_string($conn, $_POST['otp']) : '';

if (empty($email) || empty($otp)) {
    echo json_encode(['success' => false, 'message' => 'Email and OTP are required']);
    exit;
}

// STEP 2: Gamita ang $current_time gikan sa PHP sa imong query
// Imbes nga NOW(), gamiton nato ang '$current_time' variable
$sql = "SELECT * FROM verification_codes 
        WHERE email = '$email' 
        AND code = '$otp' 
        AND expires_at > '$current_time' 
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    // SUCCESS: I-delete ang code para dili na magamit pag-usab
    mysqli_query($conn, "DELETE FROM verification_codes WHERE email = '$email'");
    
    echo json_encode(['success' => true, 'message' => 'OTP verified successfully']);
} else {
    // FAIL: Check nato kung expired ba gyud o mali lang ang code
    $check_exists = mysqli_query($conn, "SELECT expires_at FROM verification_codes WHERE email = '$email' AND code = '$otp'");
    
    if ($row = mysqli_fetch_assoc($check_exists)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Verification code has expired.',
            'debug_info' => 'Expiry: ' . $row['expires_at'] . ' | Current: ' . $current_time
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
    }
}

mysqli_close($conn);
?>