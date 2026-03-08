<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coach') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tracking_id = mysqli_real_escape_string($conn, $_POST['tracking_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $sql = "UPDATE concerns SET status = '$status' WHERE tracking_id = '$tracking_id'";

    if (mysqli_query($conn, $sql)) {
        
        // ============================================
        // NOTIFY STUDENT ABOUT STATUS CHANGE
        // ============================================
        require_once 'create-notification.php';
        
        // Get student's user_id and concern info
        $concern_query = "SELECT c.student_id, c.subject, u.id as user_id 
                          FROM concerns c 
                          JOIN users u ON c.student_id = u.student_id 
                          WHERE c.tracking_id = ?";
        $concern_stmt = mysqli_prepare($conn, $concern_query);
        mysqli_stmt_bind_param($concern_stmt, "s", $tracking_id);
        mysqli_stmt_execute($concern_stmt);
        $concern_result = mysqli_stmt_get_result($concern_stmt);
        $concern_data = mysqli_fetch_assoc($concern_result);
        
        if ($concern_data) {
            $short_subject = strlen($concern_data['subject']) > 40 ? substr($concern_data['subject'], 0, 40) . '...' : $concern_data['subject'];
            
            // Status-specific messages
            $status_messages = [
                'Pending' => "Your concern \"$short_subject\" has been set back to Pending.",
                'In Progress' => "Good news! Your concern \"$short_subject\" is now being reviewed.",
                'Resolved' => "Your concern \"$short_subject\" has been resolved! 🎉"
            ];
            
            $message = $status_messages[$status] ?? "Your concern \"$short_subject\" status changed to: $status";
            
            create_notification(
                $concern_data['user_id'],
                'status_changed',
                'Concern Status Updated',
                $message,
                'concern',
                $tracking_id,
                "concern-details.php?id=$tracking_id",
                $_SESSION['user_id']
            );
        }
        mysqli_stmt_close($concern_stmt);
        // ============================================
        
        echo json_encode(['success' => true, 'message' => 'Status updated to ' . $status]);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . mysqli_error($conn)]);
    }
}
exit;