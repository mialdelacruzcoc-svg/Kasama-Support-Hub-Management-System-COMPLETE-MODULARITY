<?php
require_once 'config.php';
require_once 'create-notification.php';

echo "<h1>Create Test Notification</h1>";

// Create a test notification for the current user
$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id == 0) {
    echo "❌ Not logged in!";
    exit;
}

// Create test notification
$result = create_notification(
    $user_id,
    'concern_submitted',
    'Test Notification',
    'This is a test notification to check if the system works!',
    'concern',
    'TEST-001',
    'concern-details.php?id=TEST-001',
    null
);

if ($result) {
    echo "✅ Test notification created successfully!<br><br>";
    echo "<a href='../coach-dashboard.php'>← Go to Coach Dashboard and click the 🔔 bell</a>";
} else {
    echo "❌ Failed to create notification<br>";
    echo "Error: " . mysqli_error($conn);
}
?>