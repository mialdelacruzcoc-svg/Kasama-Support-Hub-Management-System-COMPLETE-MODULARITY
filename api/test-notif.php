<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Notification System Test</h1>";

// Test 1: Check session
require_once 'config.php';
echo "<h3>1. Session Check</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ Logged in as user_id: " . $_SESSION['user_id'] . "<br>";
    echo "Role: " . $_SESSION['role'] . "<br>";
} else {
    echo "❌ Not logged in<br>";
}

// Test 2: Check if notifications table exists
echo "<h3>2. Database Table Check</h3>";
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
if (mysqli_num_rows($table_check) > 0) {
    echo "✅ 'notifications' table EXISTS<br>";
    
    // Count notifications
    $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM notifications"))['c'];
    echo "Total notifications in table: " . $count . "<br>";
} else {
    echo "❌ 'notifications' table DOES NOT EXIST<br>";
}

// Test 3: Check if create-notification.php exists
echo "<h3>3. File Check</h3>";
if (file_exists('create-notification.php')) {
    echo "✅ create-notification.php exists<br>";
} else {
    echo "❌ create-notification.php NOT FOUND<br>";
}

if (file_exists('get-notifications.php')) {
    echo "✅ get-notifications.php exists<br>";
} else {
    echo "❌ get-notifications.php NOT FOUND<br>";
}

if (file_exists('mark-notification-read.php')) {
    echo "✅ mark-notification-read.php exists<br>";
} else {
    echo "❌ mark-notification-read.php NOT FOUND<br>";
}

// Test 4: Try to fetch notifications
echo "<h3>4. Fetch Test</h3>";
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        echo "✅ Query successful<br>";
        echo "Found " . mysqli_num_rows($result) . " notifications<br>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<pre>" . print_r($row, true) . "</pre>";
        }
    } else {
        echo "❌ Query error: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "⚠️ Cannot test - not logged in<br>";
}

echo "<hr>";
echo "<p><a href='../coach-dashboard.php'>← Back to Coach Dashboard</a></p>";
?>