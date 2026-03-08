<?php
// ============================================
// FILE UPLOAD API FOR CONCERNS
// ============================================
require_once 'config.php';
header('Content-Type: application/json');

// Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Configuration
$allowed_types = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-excel' => 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'
];
$max_size = 5 * 1024 * 1024; // 5MB
$upload_base = '../uploads/concerns/';

// Get form data
$tracking_id = isset($_POST['tracking_id']) ? sanitize_input($_POST['tracking_id']) : '';
$concern_id = isset($_POST['concern_id']) ? sanitize_input($_POST['concern_id']) : '';

if (empty($tracking_id) || empty($concern_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing concern information']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] === UPLOAD_ERR_NO_FILE) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['attachment'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds server limit',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
        UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'No temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
        UPLOAD_ERR_EXTENSION => 'Upload blocked by extension'
    ];
    $msg = $error_messages[$file['error']] ?? 'Unknown upload error';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// Validate file type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$file_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!array_key_exists($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'File type not allowed. Use: JPG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX']);
    exit;
}

// Validate file size
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB']);
    exit;
}

// Create directory for this concern
$concern_folder = $upload_base . $tracking_id . '/';
if (!is_dir($concern_folder)) {
    if (!mkdir($concern_folder, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
        exit;
    }
}

// Generate unique filename
$extension = $allowed_types[$file_type];
$original_name = basename($file['name']);
$stored_name = uniqid('file_') . '_' . time() . '.' . $extension;
$file_path = $concern_folder . $stored_name;
$relative_path = 'uploads/concerns/' . $tracking_id . '/' . $stored_name;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $file_path)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

// Save to database
$student_id = $_SESSION['student_id'];
$sql = "INSERT INTO concern_attachments 
        (concern_id, tracking_id, original_name, stored_name, file_path, file_type, file_extension, file_size, uploaded_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sssssssds", 
    $concern_id, 
    $tracking_id, 
    $original_name, 
    $stored_name, 
    $relative_path, 
    $file_type, 
    $extension, 
    $file['size'], 
    $student_id
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true, 
        'message' => 'File uploaded successfully',
        'data' => [
            'id' => mysqli_insert_id($conn),
            'original_name' => $original_name,
            'file_path' => $relative_path,
            'file_size' => $file['size'],
            'file_type' => $file_type
        ]
    ]);
} else {
    // Delete the uploaded file if DB insert fails
    unlink($file_path);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>