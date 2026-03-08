<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'create-notification.php';

echo "<h1>Test Submit Notification</h1>";

// Check if create_notification function exists
if (function_exists('create_notification')) {
    echo "✅ create_notification function exists<br><br>";
} else {
    echo "❌ create_notification function NOT FOUND<br>";
    exit;
}

// Find all coaches
$coach_query = mysqli_query($conn, "SELECT id, name FROM users WHERE role = 'coach'");
$coach_count = mysqli_num_rows($coach_query);

echo "<h3>Found $coach_count coach(es):</h3>";

while ($coach = mysqli_fetch_assoc($coach_query)) {
    echo "- Coach ID: " . $coach['id'] . " - " . $coach['name'] . "<br>";
    
    // Create test notification for this coach
    $result = create_notification(
        $coach['id'],
        'concern_submitted',
        'Test: New Concern',
        'Student John submitted a concern about "Tuition Fee Question"',
        'concern',
        'COC-2026-TEST',
        'concern-details.php?id=COC-2026-TEST',
        null
    );
    
    if ($result) {
        echo "  ✅ Notification created for this coach<br>";
    } else {
        echo "  ❌ Failed to create notification<br>";
    }
}

echo "<br><hr>";
echo "<h3>Check notifications table:</h3>";

$notif_query = mysqli_query($conn, "SELECT * FROM notifications ORDER BY id DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($notif_query)) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

echo "<br><a href='../coach-dashboard.php'>← Go to Coach Dashboard</a>";
?>