<?php
// ============================================
// FR3: TRACK FAQ VIEW COUNT
// ============================================
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$faq_id = isset($_POST['faq_id']) ? intval($_POST['faq_id']) : 0;

if ($faq_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid FAQ ID']);
    exit;
}

// Increment view count
$sql = "UPDATE faqs SET view_count = view_count + 1 WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $faq_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}
?>