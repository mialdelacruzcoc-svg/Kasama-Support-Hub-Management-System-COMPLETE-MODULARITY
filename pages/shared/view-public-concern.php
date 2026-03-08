<?php
require_once '../../api/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$tracking_id = mysqli_real_escape_string($conn, $_GET['id'] ?? '');
$user_role = $_SESSION['role'];
$user_name = $_SESSION['name'];

// Get concern (only if public)
$query = "SELECT c.*, u.name as student_name 
          FROM concerns c 
          LEFT JOIN users u ON c.student_id = u.student_id 
          WHERE c.tracking_id = '$tracking_id' AND c.is_public = 1";
$result = mysqli_query($conn, $query);
$concern = mysqli_fetch_assoc($result);

if (!$concern) {
    echo "<script>alert('Concern not found or not public.'); window.location.href='../student/existing-concerns.php';</script>";
    exit;
}

// Get responses
$responses = [];
$resp_result = mysqli_query($conn, "SELECT * FROM concern_responses WHERE tracking_id = '$tracking_id' ORDER BY created_at ASC");
if ($resp_result) {
    while ($resp = mysqli_fetch_assoc($resp_result)) {
        $responses[] = $resp;
    }
}

$display_name = ($concern['is_anonymous'] == 1) ? "Anonymous Student" : $concern['student_name'];
$avatar_text = ($concern['is_anonymous'] == 1) ? "?" : strtoupper(substr($concern['student_name'], 0, 2));

// Get initials for current user
$words = explode(" ", $user_name);
$initials = "";
foreach ($words as $w) { if(!empty($w)) $initials .= strtoupper($w[0]); }
$display_initials = substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Concern - Kasama Support Hub</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/shared-styles.css">
    <link rel="stylesheet" href="../../css/view-public-concern-styles.css">
    <link rel="stylesheet" href="../../css/dark-mode.css">
    <script src="../../js/dark-mode.js"></script>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-content">
            <div class="nav-left"><span class="nav-title">Kasama Support Hub</span></div>
            <div class="nav-right">
                <a href="../../api/logout.php" style="color:white; text-decoration:none; font-weight:bold;">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="../../images/phinma-logo.png" alt="Logo" class="header-logo">
                <span class="header-title">View Shared Concern</span>
            </div>
            <div class="header-right">
                <button class="btn-back" onclick="window.location.href='../student/existing-concerns.php'">← Back to Existing Concerns</button>
                <div class="user-profile">
                    <div class="user-avatar"><?php echo $display_initials; ?></div>
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <div class="concern-container">
                <!-- Privacy Notice -->
                <div class="privacy-notice">
                    <span class="icon">🔒</span>
                    <span class="text">This concern was shared publicly by the student. Personal details are protected based on their privacy settings.</span>
                </div>
                
                <!-- Concern Card -->
                <div class="concern-card">
                    <div class="concern-header">
                        <span class="category-badge"><?php echo htmlspecialchars($concern['category']); ?></span>
                        <h1 class="concern-title"><?php echo htmlspecialchars($concern['subject']); ?></h1>
                        <div class="concern-meta">
                            <div class="meta-item">
                                <div class="meta-avatar"><?php echo $avatar_text; ?></div>
                                <span><?php echo htmlspecialchars($display_name); ?></span>
                            </div>
                            <div class="meta-item">
                                📅 <?php echo date('M d, Y', strtotime($concern['created_at'])); ?>
                            </div>
                            <?php $status_class = 'status-' . strtolower(str_replace(' ', '-', $concern['status'])); ?>
                            <span class="status-badge <?php echo $status_class; ?>"><?php echo $concern['status']; ?></span>
                        </div>
                    </div>
                    
                    <div class="concern-body">
                        <p class="concern-description"><?php echo nl2br(htmlspecialchars($concern['description'])); ?></p>
                    </div>
                    
                    <!-- Responses -->
                    <div class="responses-section">
                        <h3 class="responses-title">💬 Coach Responses (<?php echo count($responses); ?>)</h3>
                        <?php if (count($responses) > 0): ?>
                            <?php foreach ($responses as $resp): ?>
                            <div class="response-item">
                                <div class="response-header">
                                    <span class="response-author">🧑‍🏫 <?php echo htmlspecialchars($resp['responder_name']); ?></span>
                                    <span class="response-time"><?php echo date('M d, Y - h:i A', strtotime($resp['created_at'])); ?></span>
                                </div>
                                <div class="response-message"><?php echo nl2br(htmlspecialchars($resp['message'])); ?></div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-responses">
                                <p>⏳ No responses yet. A coach will review this concern soon.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>