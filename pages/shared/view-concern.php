<?php
require_once '../../api/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../../index.php');
    exit;
}

$tracking_id = mysqli_real_escape_string($conn, $_GET['id'] ?? '');
$student_id = $_SESSION['student_id'];

// Only show concerns that belong to this student
$query = "SELECT * FROM concerns WHERE tracking_id = '$tracking_id' AND student_id = '$student_id'";
$result = mysqli_query($conn, $query);
$concern = mysqli_fetch_assoc($result);

if (!$concern) {
    echo "<script>alert('Concern not found!'); window.location.href='../student/my-concerns.php';</script>";
    exit;
}

// Fetch responses
$responses_query = "SELECT * FROM concern_responses WHERE tracking_id = '$tracking_id' ORDER BY created_at ASC";
$responses_result = mysqli_query($conn, $responses_query);
$responses = [];
if ($responses_result) {
    while ($resp = mysqli_fetch_assoc($responses_result)) {
        $responses[] = $resp;
    }
}

// Fetch attachments
$attachments_query = "SELECT * FROM concern_attachments WHERE tracking_id = '$tracking_id' ORDER BY uploaded_at ASC";
$attachments_result = mysqli_query($conn, $attachments_query);
$attachments = [];
if ($attachments_result) {
    while ($att = mysqli_fetch_assoc($attachments_result)) {
        $attachments[] = $att;
    }
}

$student_name = $_SESSION['name'];
$words = explode(" ", $student_name);
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
    <link rel="stylesheet" href="../../css/view-concern-styles.css">
    <link rel="stylesheet" href="../../css/dark-mode.css">
    <script src="../../js/dark-mode.js"></script>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-content">
            <div class="nav-left"><span class="nav-title">Kasama Support Hub</span></div>
            <div class="nav-right"><a href="../../api/logout.php" style="color:white; text-decoration:none; font-weight:bold;">Logout</a></div>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="../../images/phinma-logo.png" alt="Logo" class="header-logo">
                <span class="header-title">View Concern</span>
            </div>
            <div class="header-right">
                <button class="btn-back" onclick="window.location.href='../student/my-concerns.php'">← Back to My Concerns</button>
                <div class="user-profile">
                    <div class="user-avatar"><?php echo $display_initials; ?></div>
                    <span class="user-name"><?php echo htmlspecialchars($student_name); ?></span>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <div class="concern-container">
                <!-- Concern Details -->
                <div class="concern-card">
                    <div class="concern-header">
                        <span class="tracking-badge">📋 <?php echo $concern['tracking_id']; ?></span>
                        <h1 class="concern-title"><?php echo htmlspecialchars($concern['subject']); ?></h1>
                        <div class="concern-meta">
                            <span class="meta-tag">📁 <?php echo $concern['category']; ?></span>
                            <span class="meta-tag">⚡ <?php echo $concern['urgency']; ?></span>
                            <span class="meta-tag">📅 <?php echo date('M d, Y', strtotime($concern['created_at'])); ?></span>
                            <?php 
                            $status_class = 'status-' . strtolower(str_replace(' ', '-', $concern['status']));
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>"><?php echo $concern['status']; ?></span>
                        </div>
                    </div>
                    <h3 style="margin-bottom: 10px;">📝 Your Concern</h3>
                    <div class="concern-description"><?php echo nl2br(htmlspecialchars($concern['description'])); ?></div>
                </div>

                <!-- Attachments -->
                <?php if (count($attachments) > 0): ?>
                <div class="concern-card">
                    <h3 style="margin-bottom: 15px;">📎 Your Attachments (<?php echo count($attachments); ?>)</h3>
                    <?php foreach ($attachments as $att): 
                        $icons = ['pdf'=>'📄','doc'=>'📝','docx'=>'📝','jpg'=>'🖼️','jpeg'=>'🖼️','png'=>'🖼️'];
                        $icon = $icons[$att['file_extension']] ?? '📎';
                    ?>
                    <div class="attachment-item">
                        <span class="attachment-icon"><?php echo $icon; ?></span>
                        <span class="attachment-name"><?php echo htmlspecialchars($att['original_name']); ?></span>
                        <a href="<?php echo $att['file_path']; ?>" download class="btn-download">⬇️ Download</a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Coach Responses -->
                <div class="concern-card">
                    <h3 style="margin-bottom: 15px;">💬 Responses from Coach (<?php echo count($responses); ?>)</h3>
                    <div class="responses-section">
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
                                <p>⏳ No responses yet.</p>
                                <p style="font-size: 13px; margin-top: 5px;">A coach will review your concern soon!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>