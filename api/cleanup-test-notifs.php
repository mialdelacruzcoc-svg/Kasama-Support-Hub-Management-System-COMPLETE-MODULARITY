<?php
require_once 'config.php';

echo "<h2>Notification Cleanup</h2>";

// Show all notifications first
echo "<h3>Current Notifications:</h3>";
$all = mysqli_query($conn, "SELECT id, title, message FROM notifications ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($all)) {
    echo "ID: " . $row['id'] . " - <b>" . $row['title'] . "</b> - " . $row['message'] . "<br>";
}

echo "<hr>";

// Delete test notifications (multiple patterns)
$sql = "DELETE FROM notifications WHERE 
        title LIKE '%Test%' 
        OR title LIKE '%test%'
        OR message LIKE '%test%'
        OR message LIKE '%Test%'
        OR reference_id LIKE '%TEST%'
        OR reference_id LIKE '%test%'";

$result = mysqli_query($conn, $sql);
$deleted = mysqli_affected_rows($conn);

echo "<h3>✅ Deleted $deleted test notification(s)</h3>";

// Show remaining
echo "<h3>Remaining Notifications:</h3>";
$remaining = mysqli_query($conn, "SELECT id, title, message FROM notifications ORDER BY id DESC");
$count = mysqli_num_rows($remaining);

if ($count > 0) {
    while ($row = mysqli_fetch_assoc($remaining)) {
        echo "ID: " . $row['id'] . " - <b>" . $row['title'] . "</b> - " . $row['message'] . "<br>";
    }
} else {
    echo "No notifications remaining.";
}

echo "<br><br><a href='../coach-dashboard.php'>← Back to Dashboard</a>";
?>