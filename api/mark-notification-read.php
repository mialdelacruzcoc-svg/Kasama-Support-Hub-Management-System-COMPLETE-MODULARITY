<?php
require_once 'config.php';
header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Mark all as read
if (isset($_POST['mark_all']) && $_POST['mark_all'] === 'true') {
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = $user_id AND is_read = 0";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $affected = mysqli_affected_rows($conn);
        echo json_encode(['success' => true, 'message' => "Marked $affected as read"]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
    exit;
}

// Mark single notification as read (supports both 'notification_id' and 'id' parameters)
$notif_id = 0;
if (isset($_POST['notification_id'])) {
    $notif_id = intval($_POST['notification_id']);
} elseif (isset($_POST['id'])) {
    $notif_id = intval($_POST['id']);
}

if ($notif_id > 0) {
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = $notif_id AND user_id = $user_id";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'No action specified']);
?>