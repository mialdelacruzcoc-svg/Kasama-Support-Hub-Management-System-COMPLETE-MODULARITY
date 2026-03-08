<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    exit;

$email = mysqli_real_escape_string($conn, $_POST['email']);
$student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
$password = $_POST['password'];
$name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
$year_level = mysqli_real_escape_string($conn, $_POST['year_level'] ?? '');

// Validate year level
$allowed_years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
if (!in_array($year_level, $allowed_years)) {
    $year_level = null; // Store as NULL if invalid/missing
}

// Hash the password for security
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert into users table including name and year_level
if ($year_level !== null) {
    $sql = "INSERT INTO users (email, student_id, password, name, role, year_level, created_at) 
            VALUES ('$email', '$student_id', '$hashed_password', '$name', 'student', '$year_level', NOW())";
}
else {
    $sql = "INSERT INTO users (email, student_id, password, name, role, created_at) 
            VALUES ('$email', '$student_id', '$hashed_password', '$name', 'student', NOW())";
}

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Registration successful']);
}
else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>