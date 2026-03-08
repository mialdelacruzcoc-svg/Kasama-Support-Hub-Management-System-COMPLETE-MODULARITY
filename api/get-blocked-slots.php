<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$date = mysqli_real_escape_string($conn, $_GET['date'] ?? '');

if (empty($date)) {
    echo json_encode(['success' => false, 'message' => 'Date required']);
    exit;
}

// Get blocked slots from coach_availability
$blocked_sql = "SELECT time_slot FROM coach_availability 
                WHERE date = '$date' AND status = 'blocked'";
$blocked_result = mysqli_query($conn, $blocked_sql);

$blocked = [];
while ($row = mysqli_fetch_assoc($blocked_result)) {
    $blocked[] = $row['time_slot'];
}

// Get already booked slots
$booked_sql = "SELECT appointment_time FROM appointments 
               WHERE appointment_date = '$date' 
               AND status IN ('Scheduled', 'Confirmed')";
$booked_result = mysqli_query($conn, $booked_sql);

$booked = [];
while ($row = mysqli_fetch_assoc($booked_result)) {
    $booked[] = $row['appointment_time'];
}

echo json_encode([
    'success' => true,
    'blocked' => $blocked,
    'booked'  => $booked
]);
