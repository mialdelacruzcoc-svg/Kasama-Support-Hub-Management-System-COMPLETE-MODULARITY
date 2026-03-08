<?php
require_once 'config.php';
header('Content-Type: application/json');

// Only students can use this
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$tracking_id = mysqli_real_escape_string($conn, $_POST['tracking_id'] ?? '');
$message = mysqli_real_escape_string($conn, $_POST['message'] ?? '');

if (empty($tracking_id) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Tracking ID and message are required']);
    exit;
}

// Verify this concern belongs to the student
$student_id = $_SESSION['student_id'];
$concern_query = "SELECT c.id, c.status, c.subject, c.is_anonymous 
                  FROM concerns c 
                  WHERE c.tracking_id = '$tracking_id' AND c.student_id = '$student_id'";
$concern_result = mysqli_query($conn, $concern_query);

if (!$concern_result || mysqli_num_rows($concern_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Concern not found or access denied']);
    exit;
}

$concern = mysqli_fetch_assoc($concern_result);
$concern_id = $concern['id'];
$old_status = $concern['status'];

// Get student info
$responder_id = $_SESSION['user_id'];
$responder_name = $concern['is_anonymous'] ? 'Anonymous Student' : $_SESSION['name'];

// Insert student reply
$insert_sql = "INSERT INTO concern_responses (concern_id, tracking_id, responder_id, responder_name, sender_role, message) 
               VALUES ('$concern_id', '$tracking_id', '$responder_id', '$responder_name', 'student', '$message')";

if (!mysqli_query($conn, $insert_sql)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save reply: ' . mysqli_error($conn)]);
    exit;
}

// If concern was Resolved, reopen it to "In Progress"
$status_reopened = false;
if ($old_status === 'Resolved') {
    mysqli_query($conn, "UPDATE concerns SET status = 'In Progress', updated_at = NOW() WHERE tracking_id = '$tracking_id'");
    $status_reopened = true;
}

// Update the updated_at timestamp
mysqli_query($conn, "UPDATE concerns SET updated_at = NOW() WHERE tracking_id = '$tracking_id'");

// ============================================
// HANDLE OPTIONAL FILE ATTACHMENT (NEW)
// ============================================
$attachment_saved = false;
$attachment_error = null;

if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['attachment'];

    $allowed_types = [
        'image/jpeg'       => 'jpg',
        'image/png'        => 'png',
        'image/gif'        => 'gif',
        'application/pdf'  => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'
    ];
    $max_size = 5 * 1024 * 1024; // 5MB
    $upload_base = '../uploads/concerns/';

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $attachment_error = 'File upload failed (error code ' . $file['error'] . ')';
    } elseif ($file['size'] > $max_size) {
        $attachment_error = 'File is too large. Maximum size is 5MB.';
    } else {
        // Validate MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $file_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!array_key_exists($file_type, $allowed_types)) {
            $attachment_error = 'File type not allowed.';
        } else {
            // Create folder for this concern if it doesn't exist
            $concern_folder = $upload_base . $tracking_id . '/';
            if (!is_dir($concern_folder)) {
                mkdir($concern_folder, 0755, true);
            }

            // Generate unique filename
            $extension     = $allowed_types[$file_type];
            $original_name = basename($file['name']);
            $stored_name   = uniqid('file_') . '_' . time() . '.' . $extension;
            $file_path     = $concern_folder . $stored_name;
            $relative_path = 'uploads/concerns/' . $tracking_id . '/' . $stored_name;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Save to concern_attachments table (same table used by initial uploads)
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
                    $attachment_saved = true;
                } else {
                    unlink($file_path); // Clean up file if DB insert fails
                    $attachment_error = 'File saved but database record failed.';
                }
                mysqli_stmt_close($stmt);
            } else {
                $attachment_error = 'Failed to move uploaded file.';
            }
        }
    }
}
// ============================================

// ============================================
// NOTIFY ALL COACHES ABOUT STUDENT REPLY
// ============================================
require_once 'create-notification.php';

$coaches_query = "SELECT id FROM users WHERE role = 'coach'";
$coaches_result = mysqli_query($conn, $coaches_query);

$short_subject = strlen($concern['subject']) > 30 ? substr($concern['subject'], 0, 30) . '...' : $concern['subject'];
$display_name  = $concern['is_anonymous'] ? 'A student' : $_SESSION['name'];

$notif_message = $status_reopened
    ? "$display_name reopened concern: \"$short_subject\""
    : "$display_name replied to: \"$short_subject\"";

$notif_title = $status_reopened ? 'Concern Reopened 🔄' : 'New Student Reply 💬';

while ($coach = mysqli_fetch_assoc($coaches_result)) {
    create_notification(
        $coach['id'],
        'student_reply',
        $notif_title,
        $notif_message,
        'concern',
        $tracking_id,
        "concern-details.php?id=$tracking_id",
        $responder_id
    );
}
// ============================================

echo json_encode([
    'success'          => true,
    'message'          => 'Reply sent successfully',
    'status_reopened'  => $status_reopened,
    'attachment_saved' => $attachment_saved,
    'attachment_error' => $attachment_error
]);
?>