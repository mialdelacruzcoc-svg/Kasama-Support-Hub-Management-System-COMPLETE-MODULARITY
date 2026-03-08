<?php
// ============================================
// DELETE ATTACHMENT
// ============================================
require_once 'config.php';
header('Content-Type: application/json');

// Security Check - Only the uploader or coach can delete
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;

if ($attachment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid attachment ID']);
    exit;
}

// Get attachment info
$sql = "SELECT * FROM concern_attachments WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $attachment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attachment = mysqli_fetch_assoc($result);

if (!$attachment) {
    echo json_encode(['success' => false, 'message' => 'Attachment not found']);
    exit;
}

// Check permission: only uploader or coach can delete
$is_owner = ($attachment['uploaded_by'] === $_SESSION['student_id']);
$is_coach = ($_SESSION['role'] === 'coach');

if (!$is_owner && !$is_coach) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

// Delete physical file
$file_path = '../' . $attachment['file_path'];
if (file_exists($file_path)) {
    unlink($file_path);
}

// Delete from database
$sql = "DELETE FROM concern_attachments WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $attachment_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Attachment deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>