<?php
require_once 'api/config.php';

// 1. Siguraduhon nga naka-login gyud ang student
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error_msg = "";

// 2. Pag-handle sa Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'])) {
    $full_name = sanitize_input($_POST['full_name']);
    
    // I-update ang name ug is_profile_completed base sa ID column nimo
    // Note: Gigamit nato ang 'id' diri base sa imong miaging error
    $sql = "UPDATE users SET name = '$full_name', is_profile_completed = 1 WHERE id = '$user_id'";
    
    if (mysqli_query($conn, $sql)) {
        $_SESSION['name'] = $full_name; // I-update ang session para sa header initials
        header('Location: pages/student/dashboard.php');
        exit;
    } else {
        $error_msg = "Error updating profile: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Your Profile - Kasama Support Hub</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/setup-profile-styles.css">
    <link rel="stylesheet" href="css/dark-mode.css">
    <script src="js/dark-mode.js"></script>
</head>
<body>
    <div class="setup-card">
        <h2>Hapit na ta, Kasama! 🌿</h2>
        <p>Palihug isulat ang imong tibuok ngalan para makasugod na ta sa imong portal.</p>
        
        <?php if($error_msg): ?>
            <div class="error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form action="setup-profile.php" method="POST">
            <div class="input-group">
                <label for="full_name">Imong Tibuok Ngalan</label>
                <input type="text" id="full_name" name="full_name" placeholder="Juan Dela Cruz" required autofocus>
            </div>
            <button type="submit" class="btn-save">I-save ug Padayon</button>
        </form>
    </div>
</body>
</html>