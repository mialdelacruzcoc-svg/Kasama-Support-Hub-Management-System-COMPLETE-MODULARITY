<?php
require_once 'config.php';

// Set JSON header
header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in', 'data' => [], 'unread_count' => 0]);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

// Fetch notifications
$sql = "SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT $limit";
$result = mysqli_query($conn, $sql);

$notifications = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Format time ago
        $created = strtotime($row['created_at']);
        $diff = time() - $created;
        
        if ($diff < 60) {
            $time_ago = 'Just now';
        } elseif ($diff < 3600) {
            $time_ago = floor($diff / 60) . ' min ago';
        } elseif ($diff < 86400) {
            $time_ago = floor($diff / 3600) . ' hours ago';
        } else {
            $time_ago = floor($diff / 86400) . ' days ago';
        }
        
        // Icon based on type
        $icons = [
            'concern_submitted' => '📝',
            'status_changed' => '🔄',
            'response_added' => '💬',
            'appointment_booked' => '📅'
        ];
        $icon = isset($icons[$row['type']]) ? $icons[$row['type']] : '🔔';
        
        $notifications[] = [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'message' => $row['message'],
            'type' => $row['type'],
            'url' => (function($raw_url) {
    if (empty($raw_url)) return null;
    if (strpos($raw_url, 'concern-details.php') !== false) {
        return '../../pages/shared/' . $raw_url;
    } elseif (strpos($raw_url, 'faq.php') !== false) {
        return '../../pages/student/' . $raw_url;
    }
    return $raw_url;
})($row['url']),
            'is_read' => (int)$row['is_read'],
            'time_ago' => $time_ago,
            'icon' => $icon
        ];
    }
}

// Get unread count
$count_sql = "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $user_id AND is_read = 0";
$count_result = mysqli_query($conn, $count_sql);
$unread_count = 0;
if ($count_result) {
    $row = mysqli_fetch_assoc($count_result);
    $unread_count = (int)$row['cnt'];
}

echo json_encode([
    'success' => true,
    'data' => $notifications,
    'unread_count' => $unread_count
]);
?>