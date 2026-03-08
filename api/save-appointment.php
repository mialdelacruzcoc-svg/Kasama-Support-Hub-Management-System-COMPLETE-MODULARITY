<?php
header('Content-Type: application/json');
require_once 'config.php'; 

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_SESSION['student_id'] ?? $_SESSION['user_id']; 
    $student_name = $_SESSION['name'] ?? 'Student';
    $coach_id = 'COACH-001'; 
    
    $date = sanitize_input($_POST['date']);
    $time = sanitize_input($_POST['time']);
    $reason = sanitize_input($_POST['reason']);
    $linked_id = isset($_POST['linked_concern']) ? sanitize_input($_POST['linked_concern']) : null;

    // ============================================
    // VALIDATE: Check if slot is blocked by coach
    // ============================================
    $date_escaped = mysqli_real_escape_string($conn, $date);
    $time_escaped = mysqli_real_escape_string($conn, $time);

    $blocked_check = mysqli_query($conn, "SELECT id FROM coach_availability 
                                          WHERE date = '$date_escaped' 
                                          AND time_slot = '$time_escaped' 
                                          AND status = 'blocked'");
    if (mysqli_num_rows($blocked_check) > 0) {
        echo json_encode(['success' => false, 'message' => 'This time slot has been blocked by the coach. Please choose another slot.']);
        exit;
    }

    // VALIDATE: Check if slot is already booked
    $booked_check = mysqli_query($conn, "SELECT id FROM appointments 
                                         WHERE appointment_date = '$date_escaped' 
                                         AND appointment_time = '$time_escaped' 
                                         AND status IN ('Scheduled', 'Confirmed')");
    if (mysqli_num_rows($booked_check) > 0) {
        echo json_encode(['success' => false, 'message' => 'This time slot is already booked. Please choose another slot.']);
        exit;
    }
    // ============================================

    $sql = "INSERT INTO appointments (student_id, coach_id, appointment_date, appointment_time, reason, linked_concern_id, status) 
            VALUES ('$student_id', '$coach_id', '$date_escaped', '$time_escaped', '$reason', '$linked_id', 'Scheduled')";

    if (mysqli_query($conn, $sql)) {
        $appointment_id = mysqli_insert_id($conn);
        
        // ============================================
        // NOTIFY ALL COACHES ABOUT NEW APPOINTMENT
        // ============================================
        require_once 'create-notification.php';
        
        $coaches_query = "SELECT id FROM users WHERE role = 'coach'";
        $coaches_result = mysqli_query($conn, $coaches_query);
        
        $formatted_date = date('M d, Y', strtotime($date));
        
        while ($coach = mysqli_fetch_assoc($coaches_result)) {
            create_notification(
                $coach['id'],
                'new_appointment',
                'New Appointment Request 📅',
                "$student_name booked for $formatted_date at $time",
                'appointment',
                $appointment_id,
                'coach-appointments.php',
                $_SESSION['user_id']
            );
        }
        // ============================================
        
        echo json_encode(['success' => true, 'message' => 'Appointment booked successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
}
exit;
?>