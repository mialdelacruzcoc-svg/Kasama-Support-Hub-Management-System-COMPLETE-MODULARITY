<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'all';
$limit = intval($_GET['limit'] ?? 20);
$offset = intval($_GET['offset'] ?? 0);

// Build query based on filter
$where = "WHERE user_id = '$user_id'";

switch ($filter) {
    case 'unread':
        $where .= " AND is_read = 0";
        break;
    case 'concern':
        $where .= " AND type IN ('concern_submitted', 'new_concern', 'status_changed')";
        break;
    case 'appointment':
        $where .= " AND (type LIKE 'appointment%' OR type = 'new_appointment')";
        break;
    case 'reply':
        $where .= " AND type IN ('student_reply', 'response_added')";
        break;
}

$sql = "SELECT * FROM notifications $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);

$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
}

echo json_encode([
    'success' => true,
    'notifications' => $notifications
]);
?>