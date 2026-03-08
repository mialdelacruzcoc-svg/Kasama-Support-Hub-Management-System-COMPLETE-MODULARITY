<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. Starting<br>";

require_once 'api/config.php';
echo "2. Config loaded<br>";

echo "3. Session: ";
print_r($_SESSION);
echo "<br>";

echo "4. Role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";
echo "5. Student ID: " . ($_SESSION['student_id'] ?? 'NOT SET') . "<br>";

// Test the API
echo "<br>6. Testing get-my-concerns.php...<br>";
$student_id = $_SESSION['student_id'] ?? '';
$sql = "SELECT * FROM concerns WHERE student_id = '$student_id' LIMIT 3";
$result = mysqli_query($conn, $sql);

if ($result) {
    echo "7. Query OK - Found " . mysqli_num_rows($result) . " concerns<br>";
} else {
    echo "7. Query ERROR: " . mysqli_error($conn) . "<br>";
}

echo "<br><a href='my-concerns.php'>Try My Concerns Page</a>";
?>