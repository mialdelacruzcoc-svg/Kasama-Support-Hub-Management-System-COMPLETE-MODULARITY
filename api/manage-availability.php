<?php
// ============================================
// FR13: COACH CALENDAR AVAILABILITY MANAGEMENT
// ============================================
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================
// GET AVAILABILITY FOR A DATE RANGE
// ============================================
if ($action === 'get' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $start_date = $_GET['start'] ?? date('Y-m-d');
    $end_date = $_GET['end'] ?? date('Y-m-d', strtotime('+14 days'));
    
    // Get blocked slots
    $blocked_sql = "SELECT date, time_slot, status, notes FROM coach_availability 
                    WHERE date BETWEEN '$start_date' AND '$end_date'";
    $blocked_result = mysqli_query($conn, $blocked_sql);
    
    $availability = [];
    while ($row = mysqli_fetch_assoc($blocked_result)) {
        $availability[$row['date']][$row['time_slot']] = [
            'status' => $row['status'],
            'notes' => $row['notes']
        ];
    }
    
    // Get booked appointments
    $booked_sql = "SELECT appointment_date, appointment_time, u.name as student_name, reason
                   FROM appointments a 
                   JOIN users u ON a.student_id = u.student_id
                   WHERE appointment_date BETWEEN '$start_date' AND '$end_date'
                   AND status IN ('Scheduled', 'Confirmed')";
    $booked_result = mysqli_query($conn, $booked_sql);
    
    $bookings = [];
    while ($row = mysqli_fetch_assoc($booked_result)) {
        $bookings[$row['appointment_date']][$row['appointment_time']] = [
            'student_name' => $row['student_name'],
            'reason' => $row['reason']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'availability' => $availability,
        'bookings' => $bookings
    ]);
    exit;
}

// ============================================
// BLOCK/UNBLOCK TIME SLOT
// ============================================
if ($action === 'block' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $time_slot = mysqli_real_escape_string($conn, $_POST['time_slot']);
    $status = $_POST['status'] ?? 'blocked';
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    
    if (empty($date) || empty($time_slot)) {
        echo json_encode(['success' => false, 'message' => 'Date and time slot required']);
        exit;
    }
    
    // Check if slot has a booking
    $check_booking = mysqli_query($conn, "SELECT id FROM appointments 
                                          WHERE appointment_date = '$date' 
                                          AND appointment_time = '$time_slot'
                                          AND status IN ('Scheduled', 'Confirmed')");
    
    if (mysqli_num_rows($check_booking) > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot block - slot has an active booking']);
        exit;
    }
    
    if ($status === 'available') {
        // Remove block
        $sql = "DELETE FROM coach_availability WHERE date = '$date' AND time_slot = '$time_slot'";
    } else {
        // Add/update block
        $sql = "INSERT INTO coach_availability (date, time_slot, status, notes) 
                VALUES ('$date', '$time_slot', '$status', '$notes')
                ON DUPLICATE KEY UPDATE status = '$status', notes = '$notes'";
    }
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Availability updated']);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
    exit;
}

// ============================================
// BLOCK ENTIRE DAY
// ============================================
if ($action === 'block_day' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? 'Day blocked');
    
    if (empty($date)) {
        echo json_encode(['success' => false, 'message' => 'Date required']);
        exit;
    }
    
    // Check for existing bookings
    $check_bookings = mysqli_query($conn, "SELECT id FROM appointments 
                                           WHERE appointment_date = '$date'
                                           AND status IN ('Scheduled', 'Confirmed')");
    
    if (mysqli_num_rows($check_bookings) > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot block day - has active bookings']);
        exit;
    }
    
    $time_slots = ['8:00 AM', '9:30 AM', '11:00 AM', '1:30 PM', '3:00 PM', '4:30 PM'];
    
    foreach ($time_slots as $slot) {
        $sql = "INSERT INTO coach_availability (date, time_slot, status, notes) 
                VALUES ('$date', '$slot', 'blocked', '$notes')
                ON DUPLICATE KEY UPDATE status = 'blocked', notes = '$notes'";
        mysqli_query($conn, $sql);
    }
    
    echo json_encode(['success' => true, 'message' => 'Day blocked successfully']);
    exit;
}

// ============================================
// UNBLOCK ENTIRE DAY
// ============================================
if ($action === 'unblock_day' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    
    $sql = "DELETE FROM coach_availability WHERE date = '$date' AND status = 'blocked'";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Day unblocked']);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>