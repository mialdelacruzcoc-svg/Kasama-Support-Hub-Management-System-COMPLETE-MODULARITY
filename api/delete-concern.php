<?php
require_once 'config.php';
header('Content-Type: application/json');

// Check if student is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Not a student']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$tracking_id = isset($_POST['tracking_id']) ? mysqli_real_escape_string($conn, $_POST['tracking_id']) : '';

if (empty($tracking_id)) {
    echo json_encode(['success' => false, 'message' => 'No tracking ID provided']);
    exit;
}

$student_id = mysqli_real_escape_string($conn, $_SESSION['student_id']);

// Check if concern exists and belongs to this student
$check_sql = "SELECT id FROM concerns WHERE tracking_id = '$tracking_id' AND student_id = '$student_id'";
$check_result = mysqli_query($conn, $check_sql);

if (!$check_result) {
    echo json_encode(['success' => false, 'message' => 'Query error: ' . mysqli_error($conn)]);
    exit;
}

if (mysqli_num_rows($check_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Concern not found or does not belong to you']);
    exit;
}

// Delete related notifications (ignore error if table doesn't exist)
@mysqli_query($conn, "DELETE FROM notifications WHERE reference_id = '$tracking_id'");

// Delete the concern
$delete_sql = "DELETE FROM concerns WHERE tracking_id = '$tracking_id' AND student_id = '$student_id'";
$result = mysqli_query($conn, $delete_sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Delete failed: ' . mysqli_error($conn)]);
    exit;
}

if (mysqli_affected_rows($conn) > 0) {
    echo json_encode(['success' => true, 'message' => 'Concern deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'No rows deleted']);
}
?>