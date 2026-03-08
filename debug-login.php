<?php
require_once 'api/config.php';

$id_to_check = 'COACH-001'; 
$sql = "SELECT id, student_id, password, role FROM users WHERE student_id = '$id_to_check'";
$result = mysqli_query($conn, $sql);

if ($user = mysqli_fetch_assoc($result)) {
    echo "✅ User found in database!<br>";
    echo "ID in DB: " . $user['student_id'] . "<br>";
    echo "Role in DB: " . $user['role'] . "<br>";
    
    $pass_to_test = 'Hannah_Admin_2026!';
    if (password_verify($pass_to_test, $user['password'])) {
        echo "✅ Password is CORRECT and MATCHES the hash.";
    } else {
        echo "❌ Password DOES NOT match the hash. Run the fix-admin.php script again.";
    }
} else {
    echo "❌ User '$id_to_check' NOT found in database. Check your student_id column in phpMyAdmin.";
}
?>