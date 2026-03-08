<?php
require_once 'config.php';
header('Content-Type: application/json');

// Check session
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session error. Please logout and login again.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Generate Unique IDs
    $tracking_id = "COC-" . date("Y") . "-" . rand(1000, 9999);
    $concern_id = generate_concern_id(); 
    
    // 2. Get student info from session
    $student_id = $_SESSION['student_id'];
    $user_id = $_SESSION['user_id'];
    $student_name = $_SESSION['name'] ?? '';
    $student_email = $_SESSION['email'] ?? '';
    
    // 3. Sanitize Inputs
    $category = mysqli_real_escape_string($conn, $_POST['category'] ?? '');
    $urgency = mysqli_real_escape_string($conn, $_POST['urgency'] ?? '');
    $subject = mysqli_real_escape_string($conn, $_POST['subject'] ?? '');
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
    $is_anonymous = isset($_POST['anonymous']) ? 1 : 0;
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    // Sanitize student info
    $student_name_escaped = mysqli_real_escape_string($conn, $student_name);
    $student_email_escaped = mysqli_real_escape_string($conn, $student_email);

    // 4. Insert concern (with ALL columns)
    $sql = "INSERT INTO concerns (
                tracking_id, 
                concern_id,
                user_id,
                student_id, 
                student_name,
                student_email,
                category, 
                subject, 
                description, 
                urgency, 
                is_anonymous, 
                is_public, 
                status
            ) VALUES (
                '$tracking_id', 
                '$concern_id',
                '$user_id',
                '$student_id', 
                '$student_name_escaped',
                '$student_email_escaped',
                '$category', 
                '$subject', 
                '$description', 
                '$urgency', 
                '$is_anonymous', 
                '$is_public', 
                'Pending'
            )";

    if (mysqli_query($conn, $sql)) {
        
        // ============================================
        // NOTIFY ALL COACHES ABOUT NEW CONCERN
        // ============================================
        require_once 'create-notification.php';
        
        $display_name = $is_anonymous ? 'Anonymous Student' : $student_name;
        $short_subject = strlen($subject) > 50 ? substr($subject, 0, 50) . '...' : $subject;
        
        // Get all coaches and notify them
        $coach_query = mysqli_query($conn, "SELECT id FROM users WHERE role = 'coach'");
        
        while ($coach = mysqli_fetch_assoc($coach_query)) {
            create_notification(
                $coach['id'],                              // user_id (coach)
                'concern_submitted',                       // type
                'New Concern Submitted',                   // title
                "$display_name submitted: \"$short_subject\"",  // message
                'concern',                                 // reference_type
                $tracking_id,                              // reference_id
                "concern-details.php?id=$tracking_id",     // url
                $_SESSION['user_id']                       // sender_id
            );
        }
        // ============================================
        
        echo json_encode(['success' => true, 'data' => ['tracking_id' => $tracking_id, 'concern_id' => $concern_id]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . mysqli_error($conn)]);
    }
}
exit;
?>