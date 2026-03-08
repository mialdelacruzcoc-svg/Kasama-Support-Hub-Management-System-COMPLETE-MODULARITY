<?php
// ============================================
// FR12: AUTOMATED APPOINTMENT REMINDERS
// Run this via CRON job every 30 minutes
// ============================================
require_once 'config.php';
require_once 'send-email-notification.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

$now = new DateTime();
$results = ['24hr' => 0, '1hr' => 0, 'daily' => 0];

// ============================================
// 1. SEND 24-HOUR REMINDERS
// ============================================
$tomorrow = clone $now;
$tomorrow->modify('+24 hours');
$tomorrow_date = $tomorrow->format('Y-m-d');
$tomorrow_time_start = $tomorrow->format('H:i');
$tomorrow_time_end = $tomorrow->modify('+30 minutes')->format('H:i');

$sql_24hr = "SELECT a.*, u.name as student_name, u.email as student_email 
             FROM appointments a 
             JOIN users u ON a.student_id = u.student_id
             WHERE a.appointment_date = '$tomorrow_date'
             AND a.appointment_time BETWEEN '$tomorrow_time_start' AND '$tomorrow_time_end'
             AND a.status IN ('Scheduled', 'Confirmed')
             AND a.id NOT IN (SELECT appointment_id FROM appointment_reminders WHERE reminder_type = '24hr' AND sent_at IS NOT NULL)";

$result_24hr = mysqli_query($conn, $sql_24hr);

while ($apt = mysqli_fetch_assoc($result_24hr)) {
    $date_formatted = date('l, F j, Y', strtotime($apt['appointment_date']));
    
    $email_result = send_notification_email(
        $apt['student_email'],
        $apt['student_name'],
        '⏰ Appointment Reminder - Tomorrow',
        'Appointment Reminder',
        "
        <p>Hi <strong>{$apt['student_name']}</strong>,</p>
        <p>This is a friendly reminder that you have an appointment <strong>tomorrow</strong>:</p>
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <p><strong>📅 Date:</strong> {$date_formatted}</p>
            <p><strong>🕐 Time:</strong> {$apt['appointment_time']}</p>
            <p><strong>📝 Purpose:</strong> {$apt['reason']}</p>
        </div>
        <p>Please arrive on time. If you need to reschedule, please do so as soon as possible.</p>
        ",
        'View My Appointments',
        'http://localhost/kasama/student-dashboard.php'
    );
    
    if ($email_result['success']) {
        // Mark as sent
        mysqli_query($conn, "INSERT INTO appointment_reminders (appointment_id, reminder_type, sent_at) 
                            VALUES ({$apt['id']}, '24hr', NOW())
                            ON DUPLICATE KEY UPDATE sent_at = NOW()");
        $results['24hr']++;
    }
}

// ============================================
// 2. SEND 1-HOUR REMINDERS
// ============================================
$one_hour = clone $now;
$one_hour->modify('+1 hour');
$today_date = $now->format('Y-m-d');
$one_hour_time_start = $one_hour->format('H:i');
$one_hour_time_end = $one_hour->modify('+30 minutes')->format('H:i');

$sql_1hr = "SELECT a.*, u.name as student_name, u.email as student_email 
            FROM appointments a 
            JOIN users u ON a.student_id = u.student_id
            WHERE a.appointment_date = '$today_date'
            AND a.appointment_time BETWEEN '$one_hour_time_start' AND '$one_hour_time_end'
            AND a.status IN ('Scheduled', 'Confirmed')
            AND a.id NOT IN (SELECT appointment_id FROM appointment_reminders WHERE reminder_type = '1hr' AND sent_at IS NOT NULL)";

$result_1hr = mysqli_query($conn, $sql_1hr);

while ($apt = mysqli_fetch_assoc($result_1hr)) {
    $email_result = send_notification_email(
        $apt['student_email'],
        $apt['student_name'],
        '⏰ Appointment in 1 Hour!',
        'Your Appointment is Soon!',
        "
        <p>Hi <strong>{$apt['student_name']}</strong>,</p>
        <p>Your appointment is <strong>in about 1 hour</strong>!</p>
        <div style='background: #fff3e0; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ef6c00;'>
            <p><strong>🕐 Time:</strong> {$apt['appointment_time']}</p>
            <p><strong>📝 Purpose:</strong> {$apt['reason']}</p>
        </div>
        <p>Please make your way to the office. See you soon!</p>
        "
    );
    
    if ($email_result['success']) {
        mysqli_query($conn, "INSERT INTO appointment_reminders (appointment_id, reminder_type, sent_at) 
                            VALUES ({$apt['id']}, '1hr', NOW())
                            ON DUPLICATE KEY UPDATE sent_at = NOW()");
        $results['1hr']++;
    }
}

// ============================================
// 3. SEND DAILY SUMMARY TO COACH (8:00 AM)
// ============================================
$current_hour = $now->format('H');

if ($current_hour >= 7 && $current_hour <= 9) {
    // Check if already sent today
    $today = $now->format('Y-m-d');
    $check_daily = mysqli_query($conn, "SELECT id FROM appointment_reminders 
                                        WHERE reminder_type = 'daily_summary' 
                                        AND DATE(sent_at) = '$today'");
    
    if (mysqli_num_rows($check_daily) == 0) {
        // Get coach email
        $coach_query = mysqli_query($conn, "SELECT id, name, email FROM users WHERE role = 'coach' LIMIT 1");
        $coach = mysqli_fetch_assoc($coach_query);
        
        if ($coach) {
            // Get today's appointments
            $apt_today = mysqli_query($conn, "SELECT a.*, u.name as student_name 
                                              FROM appointments a 
                                              JOIN users u ON a.student_id = u.student_id
                                              WHERE a.appointment_date = '$today'
                                              AND a.status IN ('Scheduled', 'Confirmed')
                                              ORDER BY a.appointment_time ASC");
            
            $apt_count = mysqli_num_rows($apt_today);
            
            if ($apt_count > 0) {
                $apt_list_html = "<table style='width:100%; border-collapse: collapse; margin: 20px 0;'>";
                $apt_list_html .= "<tr style='background: #4a7c2c; color: white;'>
                                    <th style='padding: 12px; text-align: left;'>Time</th>
                                    <th style='padding: 12px; text-align: left;'>Student</th>
                                    <th style='padding: 12px; text-align: left;'>Purpose</th>
                                  </tr>";
                
                while ($apt = mysqli_fetch_assoc($apt_today)) {
                    $short_reason = strlen($apt['reason']) > 30 ? substr($apt['reason'], 0, 30) . '...' : $apt['reason'];
                    $apt_list_html .= "<tr style='border-bottom: 1px solid #eee;'>
                                        <td style='padding: 12px;'><strong>{$apt['appointment_time']}</strong></td>
                                        <td style='padding: 12px;'>{$apt['student_name']}</td>
                                        <td style='padding: 12px;'>{$short_reason}</td>
                                       </tr>";
                }
                $apt_list_html .= "</table>";
                
                $email_result = send_notification_email(
                    $coach['email'],
                    $coach['name'],
                    "📅 Today's Appointments ({$apt_count})",
                    "Good Morning, Coach!",
                    "
                    <p>Here's your appointment schedule for <strong>today, {$today}</strong>:</p>
                    <p style='font-size: 24px; text-align: center; color: #4a7c2c; font-weight: bold;'>
                        {$apt_count} Appointment(s)
                    </p>
                    {$apt_list_html}
                    <p>Have a productive day!</p>
                    ",
                    'View All Appointments',
                    'http://localhost/kasama/coach-appointments.php'
                );
                
                if ($email_result['success']) {
                    mysqli_query($conn, "INSERT INTO appointment_reminders (appointment_id, reminder_type, sent_at) 
                                        VALUES (0, 'daily_summary', NOW())");
                    $results['daily']++;
                }
            }
        }
    }
}

// Output results (for cron log)
echo json_encode([
    'success' => true,
    'sent' => $results,
    'timestamp' => $now->format('Y-m-d H:i:s')
]);
?>