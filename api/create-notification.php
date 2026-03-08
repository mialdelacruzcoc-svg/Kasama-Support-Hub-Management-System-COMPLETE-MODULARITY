<?php
// ============================================
// CREATE NOTIFICATION HELPER
// This file is included by other APIs to create notifications
// ============================================

// Only include config if not already included
if (!isset($conn)) {
    require_once 'config.php';
}

/**
 * Create a notification
 */
function create_notification($user_id, $type, $title, $message, $reference_type = null, $reference_id = null, $url = null, $sender_id = null) {
    global $conn;
    
    // Handle null sender_id
    if ($sender_id === null) {
        $sql = "INSERT INTO notifications (user_id, type, title, message, reference_type, reference_id, url, is_read, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())";
        
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            error_log("Create notification prepare error: " . mysqli_error($conn));
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "issssss", 
            $user_id,
            $type,
            $title,
            $message,
            $reference_type,
            $reference_id,
            $url
        );
    } else {
        $sql = "INSERT INTO notifications (user_id, sender_id, type, title, message, reference_type, reference_id, url, is_read, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())";
        
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            error_log("Create notification prepare error: " . mysqli_error($conn));
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "iissssss", 
            $user_id,
            $sender_id,
            $type,
            $title,
            $message,
            $reference_type,
            $reference_id,
            $url
        );
    }
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

/**
 * Create notification for all coaches
 */
function notify_all_coaches($type, $title, $message, $reference_type = null, $reference_id = null, $url = null, $sender_id = null) {
    global $conn;
    
    $sql = "SELECT id FROM users WHERE role = 'coach'";
    $result = mysqli_query($conn, $sql);
    
    $success = true;
    while ($coach = mysqli_fetch_assoc($result)) {
        if (!create_notification($coach['id'], $type, $title, $message, $reference_type, $reference_id, $url, $sender_id)) {
            $success = false;
        }
    }
    
    return $success;
}

/**
 * Get user ID from student_id
 */
function get_user_id_from_student_id($student_id) {
    global $conn;
    
    $student_id = mysqli_real_escape_string($conn, $student_id);
    $sql = "SELECT id FROM users WHERE student_id = '$student_id'";
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['id'];
    }
    return null;
}
?>