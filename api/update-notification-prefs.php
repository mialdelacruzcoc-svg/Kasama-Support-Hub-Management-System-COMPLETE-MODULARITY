<?php
require_once 'config.php';
header('Content-Type: application/json');

// Must be logged in as coach
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$prefs_raw = isset($_POST['prefs']) ? $_POST['prefs'] : '';

// Validate JSON
$prefs = json_decode($prefs_raw, true);
if (!is_array($prefs)) {
    echo json_encode(['success' => false, 'message' => 'Invalid preferences data']);
    exit;
}

// Whitelist allowed keys and ensure boolean values
$allowed_keys = ['email_new_concern', 'email_student_reply', 'email_appointment'];
$clean_prefs = [];
foreach ($allowed_keys as $key) {
    $clean_prefs[$key] = isset($prefs[$key]) ? (bool)$prefs[$key] : true;
}

$prefs_json = json_encode($clean_prefs);

// Update database
$stmt = mysqli_prepare($conn, "UPDATE users SET notification_prefs = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "si", $prefs_json, $user_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Notification preferences saved']);
}
else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}
?>
