<?php
require_once '../../api/config.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. Profile Check
$check_sql = "SELECT is_profile_completed FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $check_sql);

if ($result) {
    $user_data = mysqli_fetch_assoc($result);
    if ($user_data && $user_data['is_profile_completed'] == 0) {
        header('Location: ../../setup-profile.php');
        exit;
    }
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['name'];

// 3. Initials Logic
$words = explode(" ", $student_name);
$initials = "";
foreach ($words as $w) { if(!empty($w)) $initials .= strtoupper($w[0]); }
$display_initials = substr($initials, 0, 2);

// 4. Stats & Data Queries
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved
    FROM concerns WHERE student_id = '$student_id'";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $stats_query));

$concerns_list = mysqli_query($conn, "SELECT * FROM concerns WHERE student_id = '$student_id' ORDER BY created_at DESC");
$appointments_list = mysqli_query($conn, "SELECT * FROM appointments WHERE student_id = '$student_id' ORDER BY appointment_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"> 
    <title>Student Dashboard - Kasama Support Hub</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/student-dashboard-styles.css">
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
                <?php include '../../includes/notification-bell.php'; ?>
                <a href="../../api/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="../../images/phinma-logo.png" alt="Logo" class="header-logo">
                <span class="header-title">Student Portal</span>
            </div>
            <div class="header-right">
                <div class="user-profile">
                    <div class="user-avatar"><?php echo $display_initials; ?></div>
                    <span class="user-name"><?php echo $student_name; ?></span>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number green"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="stat-label">Total Concerns</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number orange"><?php echo $stats['pending'] ?? 0; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-bar">
                <button class="btn-submit btn-primary-action" onclick="window.location.href='faq.php'">
                    ❓ Frequently Asked Questions
                </button>
                <button class="btn-submit" onclick="window.location.href='submit-concern.php'">
                    ➕ Submit New Concern
                </button>
                <button class="btn-submit" onclick="window.location.href='my-concerns.php'">
                    📋 My Concerns
                </button>
                <button class="btn-submit" onclick="window.location.href='book-appointment.php'">
                    📅 Book Appointment
                </button>
                <button class="btn-submit" onclick="window.location.href='existing-concerns.php'">
                     Existing Concerns
                </button>
            </div>

            <!-- Appointments Section -->
            <h2 class="section-header">📅 My Appointments</h2>
            
            <!-- Mobile Cards (shown on mobile) -->
            <div class="mobile-card-list">
                <?php 
                mysqli_data_seek($appointments_list, 0);
                if(mysqli_num_rows($appointments_list) > 0): 
                    while($apt = mysqli_fetch_assoc($appointments_list)): 
                        $status_class = strtolower(str_replace(' ', '-', $apt['status']));
                ?>
                <div class="mobile-card">
                    <div class="mobile-card-info">
                        <div class="mobile-card-title"><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></div>
                        <div class="mobile-card-subtitle"><?php echo $apt['appointment_time']; ?></div>
                    </div>
                    <span class="mobile-card-badge badge-<?php echo $status_class; ?>"><?php echo $apt['status']; ?></span>
                </div>
                <?php endwhile; else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📅</div>
                    <p>No appointments scheduled</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Desktop Table (hidden on mobile) -->
            <div class="mobile-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($appointments_list, 0);
                        if(mysqli_num_rows($appointments_list) > 0): 
                            while($apt = mysqli_fetch_assoc($appointments_list)): 
                        ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></td>
                            <td><?php echo $apt['appointment_time']; ?></td>
                            <td><span class="badge badge-<?php echo strtolower($apt['status']); ?>"><?php echo $apt['status']; ?></span></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="3" class="no-data-cell">No appointments scheduled</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Concerns Section -->
            <h2 class="section-header">📝 Recent Concerns</h2>
            
            <!-- Mobile Cards (shown on mobile) -->
            <div class="mobile-card-list">
                <?php 
                mysqli_data_seek($concerns_list, 0);
                if(mysqli_num_rows($concerns_list) > 0): 
                    while($row = mysqli_fetch_assoc($concerns_list)): 
                        $status_class = strtolower(str_replace(' ', '-', $row['status']));
                ?>
                <div class="mobile-card" onclick="window.location.href='../shared/concern-details.php?id=<?php echo $row['tracking_id']; ?>'">
                    <div class="mobile-card-info">
                        <div class="mobile-card-title"><?php echo htmlspecialchars($row['subject']); ?></div>
                        <div class="mobile-card-subtitle">#<?php echo substr($row['tracking_id'], -6); ?> • <?php echo date('M d', strtotime($row['created_at'])); ?></div>
                    </div>
                    <span class="mobile-card-badge badge-<?php echo $status_class; ?>"><?php echo $row['status']; ?></span>
                </div>
                <?php endwhile; else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📝</div>
                    <p>No concerns submitted yet</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Desktop Table (hidden on mobile) -->
            <div class="mobile-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($concerns_list, 0);
                        if(mysqli_num_rows($concerns_list) > 0): 
                            while($row = mysqli_fetch_assoc($concerns_list)): 
                        ?>
                        <tr onclick="window.location.href='../shared/concern-details.php?id=<?php echo $row['tracking_id']; ?>'" class="clickable-row">
                            <td><span class="tracking-id">#<?php echo substr($row['tracking_id'], -6); ?></span></td>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td><span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>"><?php echo $row['status']; ?></span></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="4" class="no-data-cell">No concerns submitted yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>