<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

$total_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = '$user_id'";
$total = mysqli_fetch_assoc(mysqli_query($conn, $total_query))['count'];

$unread_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = '$user_id' AND is_read = 0";
$unread = mysqli_fetch_assoc(mysqli_query($conn, $unread_query))['count'];

echo json_encode([
    'success' => true,
    'total' => intval($total),
    'unread' => intval($unread)
]);
?>