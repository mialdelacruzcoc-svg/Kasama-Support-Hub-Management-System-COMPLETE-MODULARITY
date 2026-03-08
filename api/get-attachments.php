<?php
// ============================================
// GET ATTACHMENTS FOR A CONCERN
// ============================================
require_once 'config.php';
header('Content-Type: application/json');

// Security Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tracking_id = isset($_GET['tracking_id']) ? sanitize_input($_GET['tracking_id']) : '';

if (empty($tracking_id)) {
    echo json_encode(['success' => false, 'message' => 'Tracking ID required']);
    exit;
}

$sql = "SELECT id, original_name, file_path, file_type, file_extension, file_size, uploaded_at 
        FROM concern_attachments 
        WHERE tracking_id = ? 
        ORDER BY uploaded_at ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $tracking_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$attachments = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Format file size
    $size = $row['file_size'];
    if ($size >= 1048576) {
        $row['file_size_formatted'] = round($size / 1048576, 2) . ' MB';
    } else {
        $row['file_size_formatted'] = round($size / 1024, 2) . ' KB';
    }
    
    // Add icon based on type
    $icons = [
        'pdf' => '📄',
        'doc' => '📝',
        'docx' => '📝',
        'xls' => '📊',
        'xlsx' => '📊',
        'jpg' => '🖼️',
        'jpeg' => '🖼️',
        'png' => '🖼️',
        'gif' => '🖼️'
    ];
    $row['icon'] = $icons[$row['file_extension']] ?? '📎';
    
    $attachments[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $attachments,
    'count' => count($attachments)
]);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>