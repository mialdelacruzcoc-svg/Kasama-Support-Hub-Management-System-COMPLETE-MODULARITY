<?php
require_once 'api/config.php';
$new_pass = password_hash('Hannah_Admin_2026!', PASSWORD_DEFAULT);
$sql = "UPDATE users SET password = '$new_pass' WHERE student_id = 'COACH-001'";

if(mysqli_query($conn, $sql)) {
    echo "Success! Password for COACH-001 is now hashed and ready.";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>