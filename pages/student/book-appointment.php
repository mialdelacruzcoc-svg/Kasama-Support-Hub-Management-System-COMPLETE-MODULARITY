<?php
require_once '../../api/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../../index.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['name'];

$concerns_query = "SELECT tracking_id, subject FROM concerns WHERE student_id = '$student_id' AND status != 'Resolved'";
$concerns_result = mysqli_query($conn, $concerns_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Kasama Support Hub</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/shared-styles.css">
    <link rel="stylesheet" href="../../css/book-appointment-styles.css">
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
                <button class="nav-icon">🔔</button>
                <a href="../../api/logout.php" style="color: white; text-decoration: none; font-weight: bold; margin-left: 15px;">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="../../images/phinma-logo.png" alt="Logo" class="header-logo">
                <span class="header-title">Book Appointment</span>
            </div>
            <div class="header-right">
                <button class="btn-back" onclick="window.location.href='dashboard.php'">← Back</button>
                <div class="user-profile">
                    <div class="user-avatar" style="background:#4a7c2c; color:white; display:flex; align-items:center; justify-content:center; width:35px; height:35px; border-radius:50%; font-weight:bold;">
                        <?php echo strtoupper(substr($student_name, 0, 1)); ?>
                    </div>
                    <span class="user-name"><?php echo $student_name; ?></span>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <h1 class="page-title">📅 Schedule an Appointment</h1>
            <div class="appointment-container">
                <div class="booking-section">
                    <div class="calendar-header">
                        <h2 class="current-month" id="currentMonth">January 2026</h2>
                        <div class="calendar-nav">
                            <button onclick="previousMonth()">← Prev</button>
                            <button onclick="nextMonth()">Next →</button>
                        </div>
                    </div>

                    <div class="calendar" id="calendar">
                        <div class="calendar-day-header">Sun</div>
                        <div class="calendar-day-header">Mon</div>
                        <div class="calendar-day-header">Tue</div>
                        <div class="calendar-day-header">Wed</div>
                        <div class="calendar-day-header">Thu</div>
                        <div class="calendar-day-header">Fri</div>
                        <div class="calendar-day-header">Sat</div>
                    </div>

                    <div class="time-slots-section" id="timeSlotsSection" style="display: none;">
                        <h3>Available Time Slots</h3>
                        <div class="time-slots" id="timeSlots"></div>
                    </div>

                    <div class="booking-form" id="bookingForm" style="display: none;">
                        <h3>Appointment Details</h3>
                        <div class="selected-datetime">
                            <p><strong>Date:</strong> <span id="displayDate">-</span></p>
                            <p><strong>Time:</strong> <span id="displayTime">-</span></p>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display:block; margin-bottom:8px; font-weight:500;">Purpose of Appointment *</label>
                            <textarea id="appointmentPurpose" placeholder="Describe what you'd like to discuss..." style="width:100%; min-height:100px; padding:12px; border:1px solid #ddd; border-radius:8px;"></textarea>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display:block; margin-bottom:8px; font-weight:500;">Link to Existing Concern (Optional)</label>
                            <select id="linkConcern" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                                <option value="">None - New appointment</option>
                                <?php mysqli_data_seek($concerns_result, 0); ?>
                                <?php while ($row = mysqli_fetch_assoc($concerns_result)): ?>
                                    <option value="<?php echo $row['tracking_id']; ?>"><?php echo $row['tracking_id'] . " - " . $row['subject']; ?></option>
                                <?php
endwhile; ?>
                            </select>
                        </div>

                        <div style="display:flex; gap:12px; justify-content:flex-end; flex-wrap: wrap;">
                            <button class="btn-secondary" onclick="window.location.href='dashboard.php'">Cancel</button>
                            <button class="btn-submit" onclick="confirmBooking()" style="background:#4a7c2c; color:white; border:none; padding:12px 24px; border-radius:8px; cursor:pointer;">Confirm Appointment</button>
                        </div>
                    </div>
                </div>

                <div class="help-sidebar">
                    <div class="help-card">
                        <h3>📋 Office Hours</h3>
                        <ul>
                            <li><strong>Mon-Thu:</strong> 8:00 AM - 6:00 PM</li>
                            <li><strong>Friday:</strong> 8:00 AM - 5:00 PM</li>
                            <li><strong>Lunch:</strong> 12:00 NN - 1:00 PM</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>


    <script src="../../js/book-appointment.js"></script>
</body>
</html>