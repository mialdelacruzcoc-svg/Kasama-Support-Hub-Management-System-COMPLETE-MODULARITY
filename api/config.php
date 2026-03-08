<?php
// ============================================
// SESSION CONFIGURATION (Dapat pinaka-una)
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// DATABASE CONNECTION CONFIGURATION
// ============================================
date_default_timezone_set('Asia/Manila');

// Siguraduha nga ang DB_NAME ni-match sa imong phpMyAdmin
if (!defined('DB_SERVER')) {
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '');
    define('DB_NAME', 'kasama_hub'); // I-change kini kung lahi ang name sa phpMyAdmin
}

// Usa lang ka connection command ang gikinahanglan
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if (!$conn) {
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . mysqli_connect_error()
    ]));
}

mysqli_set_charset($conn, "utf8mb4");

// ============================================
// HELPER FUNCTIONS
// ============================================

function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

function send_json_response($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function is_coach() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'coach';
}

function is_student() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

// Generate unique concern ID (e.g., C00001)
function generate_concern_id() {
    global $conn;
    $sql = "SELECT id FROM concerns ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $new_id = $row['id'] + 1;
    } else {
        $new_id = 1;
    }
    return 'C' . str_pad($new_id, 5, '0', STR_PAD_LEFT);
}

// Session timeout logic
$inactive_timeout = 1800; 
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive_timeout)) {
    session_unset();
    session_destroy();
}
$_SESSION['last_activity'] = time();
?>