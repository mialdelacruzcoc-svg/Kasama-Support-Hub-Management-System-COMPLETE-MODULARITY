<?php
require_once 'config.php';
header('Content-Type: application/json');

// Check if coach
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Fetch all concerns with student names
$sql = "SELECT c.*, u.name as student_name, u.year_level 
        FROM concerns c 
        LEFT JOIN users u ON c.student_id = u.student_id 
        ORDER BY c.created_at DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    exit;
}

$concerns = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Format dates
    $row['created_at_formatted'] = date('M d, Y', strtotime($row['created_at']));
    $row['updated_at_formatted'] = $row['updated_at'] ? date('M d, Y', strtotime($row['updated_at'])) : '-';
    
    // Handle anonymous
    if ($row['is_anonymous'] == 1) {
        $row['student_name'] = 'Anonymous';
    }
    
    $concerns[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => [
        'concerns' => $concerns,
        'count' => count($concerns)
    ]
]);
?>