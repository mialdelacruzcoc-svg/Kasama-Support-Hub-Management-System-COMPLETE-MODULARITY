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
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Validate name
if (empty($name) || strlen($name) < 2) {
    echo json_encode(['success' => false, 'message' => 'Name must be at least 2 characters']);
    exit;
}

// Validate email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

// Check for duplicate email (exclude current user)
$check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) > 0) {
    echo json_encode(['success' => false, 'message' => 'This email is already in use by another account']);
    exit;
}

// Update profile
$update_stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ? WHERE id = ?");
mysqli_stmt_bind_param($update_stmt, "ssi", $name, $email, $user_id);

if (mysqli_stmt_execute($update_stmt)) {
    // Update session
    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
}
else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}
?>
