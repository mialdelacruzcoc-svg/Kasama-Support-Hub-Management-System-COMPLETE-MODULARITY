<?php
// ============================================
// BACKGROUND EMAIL SENDER (ASYNC)
// ============================================
ignore_user_abort(true);
set_time_limit(60);

require_once 'config.php';
require_once 'send-email-notification.php';

// Get pending emails from queue
$result = mysqli_query($conn, "SELECT * FROM email_queue WHERE sent = 0 ORDER BY created_at ASC LIMIT 10");

while ($row = mysqli_fetch_assoc($result)) {
    $email_data = json_decode($row['email_data'], true);
    
    if (!$email_data) {
        // Invalid data, mark as sent to skip
        mysqli_query($conn, "UPDATE email_queue SET sent = 1, sent_at = NOW() WHERE id = {$row['id']}");
        continue;
    }
    
    try {
        if ($email_data['type'] === 'coach_reply') {
            // Send coach reply email
            send_coach_reply_email(
                $email_data['to_email'],
                $email_data['to_name'],
                $email_data['tracking_id'],
                $email_data['subject'],
                $email_data['coach_name'],
                $email_data['message']
            );
            
            // Send status change email if applicable
            if ($email_data['status_changed'] && !empty($email_data['new_status'])) {
                send_status_change_email(
                    $email_data['to_email'],
                    $email_data['to_name'],
                    $email_data['tracking_id'],
                    $email_data['subject'],
                    $email_data['new_status']
                );
            }
        }
        
        // Mark as sent
        mysqli_query($conn, "UPDATE email_queue SET sent = 1, sent_at = NOW() WHERE id = {$row['id']}");
        
    } catch (Exception $e) {
        // Mark as failed
        $error = mysqli_real_escape_string($conn, $e->getMessage());
        mysqli_query($conn, "UPDATE email_queue SET attempts = attempts + 1, last_error = '$error' WHERE id = {$row['id']}");
    }
}

mysqli_close($conn);
echo "OK";
?>