<?php
require_once '../../api/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    header('Location: ../../index.php');
    exit;
}

$coach_name = $_SESSION['name'];

// Get all appointments
$apt_query = "SELECT a.*, u.name as student_name, u.email as student_email 
              FROM appointments a 
              JOIN users u ON a.student_id = u.student_id
              ORDER BY 
                CASE 
                    WHEN a.status = 'Scheduled' THEN 1
                    WHEN a.status = 'Reschedule Requested' THEN 2
                    WHEN a.status = 'Confirmed' THEN 3
                    WHEN a.status = 'Completed' THEN 4
                    ELSE 5
                END,
                a.appointment_date ASC";
$appointments_result = mysqli_query($conn, $apt_query);

// Stats
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments"))['count'];
$scheduled = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE status = 'Scheduled'"))['count'];
$confirmed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE status = 'Confirmed'"))['count'];
$completed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE status = 'Completed'"))['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - Kasama Support Hub</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/shared-styles.css">
    <link rel="stylesheet" href="../../css/coach-appointments-styles.css">
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
                <span class="header-title">Manage Appointments</span>
            </div>
            <div class="header-right">
                <button class="btn-back" onclick="window.location.href='dashboard.php'">← Back to Dashboard</button>
                <?php include '../../includes/notification-bell.php'; ?>
                <?php include '../../includes/profile-dropdown.php'; ?>
            </div>
        </header>

        <main class="dashboard-main">
            <div class="appointments-container">
                <h1 class="page-title">📅 Appointment Management</h1>
                
                <!-- Stats -->
                <div class="stats-row">
                    <div class="stat-box orange">
                        <div class="number"><?php echo $scheduled; ?></div>
                        <div class="label">⏳ Pending/Scheduled</div>
                    </div>
                    <div class="stat-box green">
                        <div class="number"><?php echo $confirmed; ?></div>
                        <div class="label">✅ Confirmed</div>
                    </div>
                    <div class="stat-box blue">
                        <div class="number"><?php echo $completed; ?></div>
                        <div class="label">✔️ Completed</div>
                    </div>
                    <div class="stat-box gray">
                        <div class="number"><?php echo $total; ?></div>
                        <div class="label">📊 Total</div>
                    </div>
                </div>
                
                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="filterAppointments('all')">All</button>
                    <button class="filter-tab" onclick="filterAppointments('Scheduled')">⏳ Scheduled</button>
                    <button class="filter-tab" onclick="filterAppointments('Confirmed')">✅ Confirmed</button>
                    <button class="filter-tab" onclick="filterAppointments('Reschedule Requested')">📅 Reschedule</button>
                    <button class="filter-tab" onclick="filterAppointments('Completed')">✔️ Completed</button>
                </div>
                
                <!-- Appointments Table -->
                <div class="appointments-card">
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Date & Time</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="appointmentsTable">
                            <?php if ($appointments_result && mysqli_num_rows($appointments_result) > 0): ?>
                                <?php while ($apt = mysqli_fetch_assoc($appointments_result)):
        $initials = strtoupper(substr($apt['student_name'], 0, 2));
        $status_class = strtolower(str_replace(' ', '-', $apt['status']));
        $short_reason = strlen($apt['reason']) > 40 ? substr($apt['reason'], 0, 40) . '...' : $apt['reason'];
?>
                                <tr data-status="<?php echo $apt['status']; ?>">
                                    <td>
                                        <div class="student-info">
                                            <div class="student-avatar"><?php echo $initials; ?></div>
                                            <div>
                                                <div class="student-name"><?php echo htmlspecialchars($apt['student_name']); ?></div>
                                                <div class="student-email"><?php echo htmlspecialchars($apt['student_email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="datetime">
                                        <div class="date"><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></div>
                                        <div class="time"><?php echo $apt['appointment_time']; ?></div>
                                    </td>
                                    <td>
                                        <span title="<?php echo htmlspecialchars($apt['reason']); ?>"><?php echo htmlspecialchars($short_reason); ?></span>
                                        <?php if (strlen($apt['reason']) > 40): ?>
                                            <button class="btn-action btn-view" onclick="viewDetails(<?php echo $apt['id']; ?>, '<?php echo htmlspecialchars(addslashes($apt['student_name'])); ?>', '<?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?>', '<?php echo $apt['appointment_time']; ?>', '<?php echo htmlspecialchars(addslashes($apt['reason'])); ?>', '<?php echo $apt['status']; ?>')">View</button>
                                        <?php
        endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $status_class; ?>"><?php echo $apt['status']; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($apt['status'] === 'Scheduled'): ?>
                                            <button class="btn-action btn-confirm" onclick="updateStatus(<?php echo $apt['id']; ?>, 'Confirmed')">✅ Confirm</button>
                                            <button class="btn-action btn-resched" onclick="openReschedModal(<?php echo $apt['id']; ?>)">📅 Reschedule</button>
                                        <?php
        elseif ($apt['status'] === 'Confirmed'): ?>
                                            <button class="btn-action btn-complete" onclick="updateStatus(<?php echo $apt['id']; ?>, 'Completed')">✔️ Complete</button>
                                            <button class="btn-action btn-resched" onclick="openReschedModal(<?php echo $apt['id']; ?>)">📅 Reschedule</button>
                                        <?php
        elseif ($apt['status'] === 'Reschedule Requested'): ?>
                                            <button class="btn-action btn-confirm" onclick="updateStatus(<?php echo $apt['id']; ?>, 'Confirmed')">✅ Confirm New</button>
                                            <button class="btn-action btn-cancel" onclick="updateStatus(<?php echo $apt['id']; ?>, 'Cancelled')">❌ Cancel</button>
                                        <?php
        elseif ($apt['status'] === 'Completed'): ?>
                                            <span style="color: #888; font-size: 12px;">—</span>
                                        <?php
        endif; ?>
                                    </td>
                                </tr>
                                <?php
    endwhile; ?>
                            <?php
else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <div class="icon">📅</div>
                                            <p>No appointments found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php
endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- View Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📅 Appointment Details</h3>
                <button class="modal-close" onclick="closeModal('detailsModal')">&times;</button>
            </div>
            <div id="detailsContent"></div>
        </div>
    </div>

    <!-- Reschedule Modal -->
    <div id="reschedModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📅 Request Reschedule</h3>
                <button class="modal-close" onclick="closeModal('reschedModal')">&times;</button>
            </div>
            <p style="color: #666; margin-bottom: 15px;">Send a message to the student explaining why you need to reschedule.</p>
            <input type="hidden" id="resched_apt_id">
            <textarea id="resched_message" style="width:100%; height:120px; padding:12px; border:1px solid #ddd; border-radius:8px; font-family:inherit; font-size:14px;" placeholder="Hi! I need to reschedule our appointment because..."></textarea>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button onclick="closeModal('reschedModal')" style="padding:10px 20px; border:1px solid #ddd; background:white; border-radius:6px; cursor:pointer;">Cancel</button>
                <button onclick="submitReschedule()" style="padding:10px 20px; background:#ef6c00; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">📅 Send Request</button>
            </div>
        </div>
    </div>


    <script src="../../js/coach-appointments.js"></script>
</body>
</html>