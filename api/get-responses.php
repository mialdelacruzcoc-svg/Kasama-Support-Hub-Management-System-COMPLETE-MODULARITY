<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$tracking_id = mysqli_real_escape_string($conn, $_GET['tracking_id'] ?? '');

if (empty($tracking_id)) {
    echo json_encode(['success' => false, 'message' => 'No tracking ID']);
    exit;
}

// Verify access - coaches can see all, students can only see their own
if ($_SESSION['role'] === 'student') {
    $student_id = $_SESSION['student_id'];
    $check = mysqli_query($conn, "SELECT id FROM concerns WHERE tracking_id = '$tracking_id' AND student_id = '$student_id'");
    if (mysqli_num_rows($check) === 0) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
}

// Get responses
$sql = "SELECT * FROM concern_responses WHERE tracking_id = '$tracking_id' ORDER BY created_at ASC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    exit;
}

$responses = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['created_at_formatted'] = date('M d, Y - h:i A', strtotime($row['created_at']));
    $responses[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $responses
]);
?>