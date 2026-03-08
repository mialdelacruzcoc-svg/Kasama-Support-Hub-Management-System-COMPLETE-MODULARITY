<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "DELETE FROM notifications WHERE user_id = '$user_id' AND is_read = 1";
mysqli_query($conn, $sql);

$deleted = mysqli_affected_rows($conn);

echo json_encode(['success' => true, 'deleted' => $deleted]);
?>