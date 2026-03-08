<?php
require_once 'config.php';
header('Content-Type: application/json');

// Check if student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$student_id = mysqli_real_escape_string($conn, $_SESSION['student_id']);

// Fetch student's concerns
$sql = "SELECT * FROM concerns WHERE student_id = '$student_id' ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    exit;
}

$concerns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['created_at_formatted'] = date('M d, Y - h:i A', strtotime($row['created_at']));
    $row['updated_at_formatted'] = $row['updated_at'] ? date('M d, Y - h:i A', strtotime($row['updated_at'])) : '-';
    $concerns[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $concerns
]);
?>