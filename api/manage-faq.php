<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $question = mysqli_real_escape_string($conn, $_POST['question']);
        $answer = mysqli_real_escape_string($conn, $_POST['answer']);
        $category = mysqli_real_escape_string($conn, $_POST['category']);

        $sql = "INSERT INTO faqs (question, answer, category) VALUES ('$question', '$answer', '$category')";
        
        if (mysqli_query($conn, $sql)) {
            
            // ============================================
            // NOTIFY ALL STUDENTS ABOUT NEW FAQ
            // ============================================
            require_once 'create-notification.php';
            
            $students_query = "SELECT id FROM users WHERE role = 'student'";
            $students_result = mysqli_query($conn, $students_query);
            
            $short_question = strlen($question) > 35 ? substr($question, 0, 35) . '...' : $question;
            
            while ($student = mysqli_fetch_assoc($students_result)) {
                create_notification(
                    $student['id'],
                    'new_faq',
                    'New FAQ Added 📚',
                    "New in $category: \"$short_question\"",
                    'faq',
                    null,
                    'faq.php',
                    $_SESSION['user_id']
                );
            }
            // ============================================
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        exit;
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $sql = "DELETE FROM faqs WHERE id = $id";
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
        exit;
    }
}
?>