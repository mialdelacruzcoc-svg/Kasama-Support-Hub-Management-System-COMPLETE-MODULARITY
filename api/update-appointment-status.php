<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$status = mysqli_real_escape_string($conn, $_POST['status'] ?? '');
$message = mysqli_real_escape_string($conn, $_POST['message'] ?? '');

if (!$id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Get appointment and student info
$apt_query = "SELECT a.*, u.id as student_user_id, u.name as student_name, u.email as student_email 
              FROM appointments a 
              JOIN users u ON a.student_id = u.student_id 
              WHERE a.id = $id";
$apt_result = mysqli_query($conn, $apt_query);

if (!$apt_result || mysqli_num_rows($apt_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    exit;
}

$appointment = mysqli_fetch_assoc($apt_result);
$old_status = $appointment['status'];

// Update appointment status
$update_sql = "UPDATE appointments SET status = '$status' WHERE id = $id";

if (!mysqli_query($conn, $update_sql)) {
    echo json_encode(['success' => false, 'message' => 'Failed to update: ' . mysqli_error($conn)]);
    exit;
}

// Save reschedule message if provided
if (!empty($message)) {
    $save_msg = "INSERT INTO appointment_chats (appointment_id, sender_id, sender_role, message) 
                 VALUES ($id, '{$_SESSION['user_id']}', 'coach', '$message')";
    mysqli_query($conn, $save_msg);
}

// ============================================
// NOTIFY STUDENT ABOUT STATUS CHANGE
// ============================================
require_once 'create-notification.php';

$formatted_date = date('M d, Y', strtotime($appointment['appointment_date']));
$formatted_time = $appointment['appointment_time'];
$coach_name = $_SESSION['name'] ?? 'Coach';

// Status-specific notifications
$notifications = [
    'Confirmed' => [
        'title' => 'Appointment Confirmed! ✅',
        'message' => "Your appointment on $formatted_date at $formatted_time has been confirmed by $coach_name.",
        'icon' => '✅'
    ],
    'Reschedule Requested' => [
        'title' => 'Reschedule Requested 📅',
        'message' => "Coach $coach_name requested to reschedule your appointment on $formatted_date.",
        'icon' => '📅'
    ],
    'Completed' => [
        'title' => 'Appointment Completed ✔️',
        'message' => "Your appointment on $formatted_date has been marked as completed.",
        'icon' => '✔️'
    ],
    'Cancelled' => [
        'title' => 'Appointment Cancelled ❌',
        'message' => "Your appointment on $formatted_date has been cancelled.",
        'icon' => '❌'
    ]
];

if (isset($notifications[$status]) && $appointment['student_user_id']) {
    $notif = $notifications[$status];
    
    create_notification(
        $appointment['student_user_id'],
        'appointment_' . strtolower(str_replace(' ', '_', $status)),
        $notif['title'],
        $notif['message'],
        'appointment',
        $id,
        'student-dashboard.php#appointments',
        $_SESSION['user_id']
    );
}

// ============================================
// SEND EMAIL NOTIFICATION TO STUDENT
// ============================================
if ($appointment['student_user_id'] && !empty($appointment['student_email'])) {
    require_once 'send-email-notification.php';
    
    $apt_date = $appointment['appointment_date'];
    $apt_time = $appointment['appointment_time'];
    
    switch ($status) {
        case 'Confirmed':
            send_appointment_confirmed_email(
                $appointment['student_email'],
                $appointment['student_name'],
                $apt_date,
                $apt_time,
                $coach_name
            );
            break;
            
        case 'Reschedule Requested':
            send_appointment_reschedule_email(
                $appointment['student_email'],
                $appointment['student_name'],
                $apt_date,
                $apt_time,
                $coach_name,
                $message
            );
            break;
            
        case 'Completed':
            send_appointment_completed_email(
                $appointment['student_email'],
                $appointment['student_name'],
                $apt_date,
                $apt_time
            );
            break;
    }
}
// ============================================

echo json_encode([
    'success' => true, 
    'message' => 'Status updated to ' . $status
]);
?>