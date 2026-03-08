<?php
require_once '../../api/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    header('Location: ../../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch full coach data from DB
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$coach = mysqli_fetch_assoc($result);

if (!$coach) {
    header('Location: ../../index.php');
    exit;
}

$coach_name = $coach['name'];
$coach_email = $coach['email'];
$coach_id = $coach['student_id'];
$created_at = isset($coach['created_at']) ? date('F d, Y', strtotime($coach['created_at'])) : 'N/A';

// Get initials
$words = explode(" ", $coach_name);
$initials = "";
foreach ($words as $w) {
    if (!empty($w))
        $initials .= strtoupper($w[0]);
}
$display_initials = substr($initials, 0, 2);

// Parse notification preferences
$default_prefs = [
    'email_new_concern' => true,
    'email_student_reply' => true,
    'email_appointment' => true
];
$notification_prefs = $default_prefs;
if (!empty($coach['notification_prefs'])) {
    $decoded = json_decode($coach['notification_prefs'], true);
    if (is_array($decoded)) {
        $notification_prefs = array_merge($default_prefs, $decoded);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Kasama Support Hub</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/shared-styles.css">
    <link rel="stylesheet" href="../../css/coach-profile-styles.css">
    <link rel="stylesheet" href="../../css/dark-mode.css">
    <script src="../../js/dark-mode.js"></script>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-content">
            <div class="nav-left">
                <span class="nav-title">Kasama Support Hub</span>
            </div>
            <div class="nav-right">
                <a href="../../api/logout.php" style="color:white; text-decoration:none; font-weight:bold;">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="../../images/phinma-logo.png" alt="Logo" class="header-logo">
                <span class="header-title">My Profile</span>
            </div>
            <div class="header-right">
                <button class="btn-back" onclick="window.location.href='dashboard.php'">← Back to Dashboard</button>
                <?php include '../../includes/profile-dropdown.php'; ?>
            </div>
        </header>

        <main class="dashboard-main">
            <div class="profile-container">

                <!-- Hero Card -->
                <div class="profile-hero">
                    <div class="profile-avatar-large"><?php echo $display_initials; ?></div>
                    <div class="profile-hero-info">
                        <h1 id="heroName">Coach <?php echo htmlspecialchars($coach_name); ?></h1>
                        <div class="profile-role">Guidance Coach · Kasama Support Hub</div>
                        <span class="profile-id-badge">🆔 <?php echo htmlspecialchars($coach_id); ?></span>
                    </div>
                </div>

                <!-- Account Details Section -->
                <div class="profile-section" id="accountSection">
                    <div class="profile-section-header" onclick="toggleSection('accountSection')">
                        <h2>👤 Account Details</h2>
                        <span class="section-toggle">▼</span>
                    </div>
                    <div class="profile-section-body">
                        <form id="profileForm" onsubmit="saveProfile(event)">
                            <div class="profile-field">
                                <label for="profileName">Display Name</label>
                                <input type="text" id="profileName" value="<?php echo htmlspecialchars($coach_name); ?>" required minlength="2" maxlength="100">
                            </div>
                            <div class="profile-field">
                                <label for="profileEmail">Email Address</label>
                                <input type="email" id="profileEmail" value="<?php echo htmlspecialchars($coach_email); ?>" required>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Coach ID</span>
                                <span class="info-badge"><?php echo htmlspecialchars($coach_id); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Role</span>
                                <span class="info-badge">Coach</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Member Since</span>
                                <span class="info-value"><?php echo $created_at; ?></span>
                            </div>
                            <div style="margin-top: 20px;">
                                <button type="submit" class="btn-save-profile" id="btnSaveProfile">💾 Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password Section -->
                <div class="profile-section collapsed" id="passwordSection">
                    <div class="profile-section-header" onclick="toggleSection('passwordSection')">
                        <h2>🔒 Change Password</h2>
                        <span class="section-toggle">▼</span>
                    </div>
                    <div class="profile-section-body">
                        <form id="passwordForm" onsubmit="changePassword(event)">
                            <div class="profile-field">
                                <label for="currentPassword">Current Password</label>
                                <input type="password" id="currentPassword" required placeholder="Enter your current password">
                            </div>
                            <div class="profile-field">
                                <label for="newPassword">New Password</label>
                                <input type="password" id="newPassword" required placeholder="Minimum 8 characters" minlength="8" oninput="checkPasswordStrength(this.value)">
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="strengthBar"></div>
                                </div>
                                <div class="password-strength-text" id="strengthText"></div>
                            </div>
                            <div class="profile-field">
                                <label for="confirmPassword">Confirm New Password</label>
                                <input type="password" id="confirmPassword" required placeholder="Re-enter new password" minlength="8">
                            </div>
                            <div style="margin-top: 20px;">
                                <button type="submit" class="btn-save-profile" id="btnChangePassword">🔑 Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Notification Preferences Section -->
                <div class="profile-section" id="notifSection">
                    <div class="profile-section-header" onclick="toggleSection('notifSection')">
                        <h2>🔔 Notification Preferences</h2>
                        <span class="section-toggle">▼</span>
                    </div>
                    <div class="profile-section-body">
                        <div class="toggle-row">
                            <div class="toggle-info">
                                <h4>New Concern Submitted</h4>
                                <p>Get notified when a student submits a new concern</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="prefConcern" <?php echo $notification_prefs['email_new_concern'] ? 'checked' : ''; ?> onchange="saveNotifPrefs()">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="toggle-row">
                            <div class="toggle-info">
                                <h4>Student Replies</h4>
                                <p>Get notified when a student replies to a concern thread</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="prefReply" <?php echo $notification_prefs['email_student_reply'] ? 'checked' : ''; ?> onchange="saveNotifPrefs()">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="toggle-row">
                            <div class="toggle-info">
                                <h4>Appointment Updates</h4>
                                <p>Get notified about new bookings and schedule changes</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="prefAppointment" <?php echo $notification_prefs['email_appointment'] ? 'checked' : ''; ?> onchange="saveNotifPrefs()">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="field-hint" style="margin-top: 12px;">Changes are saved automatically when toggled.</div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div class="toast" id="toast"></div>


    <script src="../../js/coach-profile.js"></script>
</body>
</html>
