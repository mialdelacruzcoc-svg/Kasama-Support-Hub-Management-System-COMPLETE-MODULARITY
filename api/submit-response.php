<?php
require_once 'config.php';
header('Content-Type: application/json');

// Only coaches can respond
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$tracking_id = mysqli_real_escape_string($conn, $_POST['tracking_id'] ?? '');
$message = mysqli_real_escape_string($conn, $_POST['message'] ?? '');
$new_status = mysqli_real_escape_string($conn, $_POST['status'] ?? '');

if (empty($tracking_id) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Tracking ID and message are required']);
    exit;
}

// Get concern info
$concern_query = "SELECT c.id, c.student_id, c.subject, c.status, u.id as student_user_id 
                  FROM concerns c 
                  LEFT JOIN users u ON c.student_id = u.student_id 
                  WHERE c.tracking_id = '$tracking_id'";
$concern_result = mysqli_query($conn, $concern_query);

if (!$concern_result || mysqli_num_rows($concern_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Concern not found']);
    exit;
}

$concern = mysqli_fetch_assoc($concern_result);
$concern_id = $concern['id'];
$responder_id = $_SESSION['user_id'];
$responder_name = $_SESSION['name'];

// Insert response
$insert_sql = "INSERT INTO concern_responses (concern_id, tracking_id, responder_id, responder_name, message) 
               VALUES ('$concern_id', '$tracking_id', '$responder_id', '$responder_name', '$message')";

if (!mysqli_query($conn, $insert_sql)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save response: ' . mysqli_error($conn)]);
    exit;
}

// Track first response time
$first_response_check = mysqli_query($conn, "SELECT first_response_at FROM concerns WHERE tracking_id = '$tracking_id'");
$first_response_data = mysqli_fetch_assoc($first_response_check);
if (empty($first_response_data['first_response_at'])) {
    mysqli_query($conn, "UPDATE concerns SET first_response_at = NOW() WHERE tracking_id = '$tracking_id'");
}

// Update status if changed
$status_changed = false;
if (!empty($new_status) && $new_status !== $concern['status']) {
    $update_sql = "UPDATE concerns SET status = '$new_status', updated_at = NOW() WHERE tracking_id = '$tracking_id'";
    mysqli_query($conn, $update_sql);
    $status_changed = true;
    
    // Track resolved time
    if ($new_status === 'Resolved') {
        mysqli_query($conn, "UPDATE concerns SET resolved_at = NOW() WHERE tracking_id = '$tracking_id'");
    }
}

// Notify student about the response
if ($concern['student_user_id']) {
    require_once 'create-notification.php';
    
    $short_subject = strlen($concern['subject']) > 30 ? substr($concern['subject'], 0, 30) . '...' : $concern['subject'];
    
    // Notification for response
    create_notification(
        $concern['student_user_id'],
        'response_added',
        'New Reply from Coach',
        "Coach $responder_name replied to your concern: \"$short_subject\"",
        'concern',
        $tracking_id,
        "concern-details.php?id=$tracking_id",
        $responder_id
    );
    
    // Additional notification if status changed
    if ($status_changed) {
        $status_messages = [
            'Pending' => "Your concern has been set back to Pending.",
            'In Progress' => "Good news! Your concern is now being reviewed.",
            'Resolved' => "Your concern has been resolved! 🎉"
        ];
        $status_msg = $status_messages[$new_status] ?? "Status changed to: $new_status";
        
        create_notification(
            $concern['student_user_id'],
            'status_changed',
            'Concern Status Updated',
            $status_msg . " - \"$short_subject\"",
            'concern',
            $tracking_id,
            "concern-details.php?id=$tracking_id",
            $responder_id
        );
    }
    
        // ============================================
    // QUEUE EMAIL (ASYNC - NON-BLOCKING)
    // ============================================
    $student_query = "SELECT email, name FROM users WHERE id = '{$concern['student_user_id']}'";
    $student_result = mysqli_query($conn, $student_query);
    $student_data = mysqli_fetch_assoc($student_result);
    
    if ($student_data && !empty($student_data['email'])) {
        // Queue email to be sent asynchronously
        $email_data = [
            'type' => 'coach_reply',
            'to_email' => $student_data['email'],
            'to_name' => $student_data['name'],
            'tracking_id' => $tracking_id,
            'subject' => $concern['subject'],
            'coach_name' => $responder_name,
            'message' => $message,
            'status_changed' => $status_changed,
            'new_status' => $new_status
        ];
        
        // Insert into email queue
        $email_json = mysqli_real_escape_string($conn, json_encode($email_data));
        mysqli_query($conn, "INSERT INTO email_queue (email_data, created_at) VALUES ('$email_json', NOW())");
        
        // Trigger background email send (non-blocking)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/send-queued-emails.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100); // Only wait 100ms
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_exec($ch);
        curl_close($ch);
    }
    // ============================================
}

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
    $max_size    = 5 * 1024 * 1024;
    $upload_base = '../uploads/concerns/';

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $attachment_error = 'File upload failed (error code ' . $file['error'] . ')';
    } elseif ($file['size'] > $max_size) {
        $attachment_error = 'File is too large. Maximum size is 5MB.';
    } else {
        $finfo     = finfo_open(FILEINFO_MIME_TYPE);
        $file_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!array_key_exists($file_type, $allowed_types)) {
            $attachment_error = 'File type not allowed.';
        } else {
            $concern_folder = $upload_base . $tracking_id . '/';
            if (!is_dir($concern_folder)) {
                mkdir($concern_folder, 0755, true);
            }

            $extension     = $allowed_types[$file_type];
            $original_name = basename($file['name']);
            $stored_name   = uniqid('file_') . '_' . time() . '.' . $extension;
            $file_path     = $concern_folder . $stored_name;
            $relative_path = 'uploads/concerns/' . $tracking_id . '/' . $stored_name;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $sql  = "INSERT INTO concern_attachments 
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
                    $responder_id
                );
                if (mysqli_stmt_execute($stmt)) {
                    $attachment_saved = true;
                } else {
                    unlink($file_path);
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

echo json_encode([
    'success'          => true,
    'message'          => 'Response saved successfully',
    'status_changed'   => $status_changed,
    'attachment_saved' => $attachment_saved,
    'attachment_error' => $attachment_error
]);