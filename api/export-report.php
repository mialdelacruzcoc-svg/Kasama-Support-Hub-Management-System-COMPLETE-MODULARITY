<?php
// ============================================
// EXPORT REPORT - CSV Download
// ============================================
require_once 'config.php';

// Security Check: Coach only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    header('Location: ../index.php');
    exit;
}

// Get period filter (same as analytics.php)
$period = $_GET['period'] ?? 'all';
$date_filter = '';

switch ($period) {
    case 'today':
        $date_filter = "AND DATE(created_at) = CURDATE()";
        $period_label = 'Today';
        break;
    case 'week':
        $date_filter = "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $period_label = 'This_Week';
        break;
    case 'month':
        $date_filter = "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $period_label = 'This_Month';
        break;
    case 'year':
        $date_filter = "AND YEAR(created_at) = YEAR(CURDATE())";
        $period_label = 'This_Year';
        break;
    default:
        $period_label = 'All_Time';
}

// Fetch concerns data
$sql = "SELECT 
            tracking_id,
            concern_id,
            student_id,
            student_name,
            category,
            subject,
            description,
            urgency,
            status,
            is_anonymous,
            created_at,
            updated_at,
            first_response_at,
            resolved_at
        FROM concerns 
        WHERE 1=1 $date_filter 
        ORDER BY created_at DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die('Query failed: ' . mysqli_error($conn));
}

// Set headers for CSV download
$filename = "Kasama_Report_" . $period_label . "_" . date('Y-m-d_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Header Row
fputcsv($output, [
    'Tracking ID',
    'Concern ID',
    'Student ID',
    'Student Name',
    'Category',
    'Subject',
    'Description',
    'Urgency',
    'Status',
    'Submitted Date',
    'First Response',
    'Resolved Date',
    'Response Time (hrs)',
    'Resolution Time (hrs)'
]);

// Data Rows
while ($row = mysqli_fetch_assoc($result)) {
    // Protect anonymous students
    $student_name = ($row['is_anonymous'] == 1) ? 'Anonymous' : $row['student_name'];
    $student_id_display = ($row['is_anonymous'] == 1) ? 'Hidden' : $row['student_id'];
    
    // Calculate response time (hours)
    $response_time = '';
    if (!empty($row['first_response_at']) && !empty($row['created_at'])) {
        $created = strtotime($row['created_at']);
        $responded = strtotime($row['first_response_at']);
        $response_time = round(($responded - $created) / 3600, 1);
    }
    
    // Calculate resolution time (hours)
    $resolution_time = '';
    if (!empty($row['resolved_at']) && !empty($row['created_at'])) {
        $created = strtotime($row['created_at']);
        $resolved = strtotime($row['resolved_at']);
        $resolution_time = round(($resolved - $created) / 3600, 1);
    }
    
    // Format dates
    $submitted_date = date('M d, Y h:i A', strtotime($row['created_at']));
    $first_response_date = !empty($row['first_response_at']) ? date('M d, Y h:i A', strtotime($row['first_response_at'])) : '-';
    $resolved_date = !empty($row['resolved_at']) ? date('M d, Y h:i A', strtotime($row['resolved_at'])) : '-';
    
    // Write row
    fputcsv($output, [
        $row['tracking_id'],
        $row['concern_id'],
        $student_id_display,
        $student_name,
        $row['category'],
        $row['subject'],
        $row['description'],
        $row['urgency'],
        $row['status'],
        $submitted_date,
        $first_response_date,
        $resolved_date,
        $response_time,
        $resolution_time
    ]);
}

fclose($output);
mysqli_close($conn);
exit;
?>