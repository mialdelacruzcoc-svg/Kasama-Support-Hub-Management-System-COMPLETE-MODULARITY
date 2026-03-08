<?php
// 1. Siguraduhon nga ang session nagdagan gyud sa sugod pa lang
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(false, 'Invalid request method');
}

$username = isset($_POST['username']) ? sanitize_input($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($username) || empty($password)) {
    send_json_response(false, 'Please enter ID and password');
}

// 2. Search user base sa imong 'student_id' column
$sql = "SELECT * FROM users WHERE student_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    
    // 3. I-verify ang password (Hashed man o Plain text)
    $input_pass = trim($password);
    $db_pass = trim($user['password']);

    if (password_verify($input_pass, $db_pass) || $input_pass === $db_pass) {
        
        // 4. I-set ang Session variables base sa imong columns
        $_SESSION['user_id'] = isset($user['user_id']) ? $user['user_id'] : $user['id']; 
        $_SESSION['student_id'] = $user['student_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];  // ✅ ADDED
        $_SESSION['role'] = $user['role'];

        $redirect = ($user['role'] === 'coach') ? 'pages/coach/dashboard.php' : 'pages/student/dashboard.php';

        send_json_response(true, 'Login successful', [
            'redirect' => $redirect,
            'name' => $user['name']
        ]);
    } else {
        send_json_response(false, 'Wrong password. Palihug i-check ang capitalization.');
    }
} else {
    send_json_response(false, 'User ID [' . $username . '] not found in database.');
}
?>