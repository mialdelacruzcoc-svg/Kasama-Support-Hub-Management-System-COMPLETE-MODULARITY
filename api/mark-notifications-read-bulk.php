<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$ids = $input['ids'] ?? [];

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'No IDs provided']);
    exit;
}

// Sanitize IDs
$ids = array_map('intval', $ids);
$ids_str = implode(',', $ids);

$sql = "UPDATE notifications SET is_read = 1 WHERE id IN ($ids_str) AND user_id = '$user_id'";
mysqli_query($conn, $sql);

echo json_encode(['success' => true, 'marked' => mysqli_affected_rows($conn)]);
?>