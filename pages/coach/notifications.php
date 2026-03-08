<?php
require_once '../../api/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    header('Location: ../../index.php');
    exit;
}

$coach_name = $_SESSION['name'];
$user_id = $_SESSION['user_id'];

// Get initials
$words = explode(" ", $coach_name);
$initials = "";
foreach ($words as $w) {
    if (!empty($w))
        $initials .= strtoupper($w[0]);
}
$display_initials = substr($initials, 0, 2);

// Get unread count
$unread_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = '$user_id' AND is_read = 0";
$unread_result = mysqli_query($conn, $unread_query);
$unread_count = mysqli_fetch_assoc($unread_result)['count'] ?? 0;

// Get total count
$total_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = '$user_id'";
$total_result = mysqli_query($conn, $total_query);
$total_count = mysqli_fetch_assoc($total_result)['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Kasama Support Hub</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/shared-styles.css">
    <link rel="stylesheet" href="../../css/coach-notifications-styles.css">
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
                <span class="header-title">All Notifications</span>
            </div>
            <div class="header-right">
                <button class="btn-back" onclick="window.location.href='dashboard.php'">← Back to Dashboard</button>
                <?php include '../../includes/profile-dropdown.php'; ?>
            </div>
        </header>

        <main class="dashboard-main">
            <div class="notifications-container">
                <!-- Header -->
                <div class="notifications-header">
                    <h1>
                        🔔 Notifications 
                        <?php if ($unread_count > 0): ?>
                        <span class="unread-badge"><?php echo $unread_count; ?> unread</span>
                        <?php
endif; ?>
                    </h1>
                </div>

                <!-- Stats -->
                <div class="stats-row">
                    <div class="stat-chip"><strong id="totalCount"><?php echo $total_count; ?></strong> Total</div>
                    <div class="stat-chip"><strong id="unreadCount"><?php echo $unread_count; ?></strong> Unread</div>
                    <div class="stat-chip"><strong id="readCount"><?php echo $total_count - $unread_count; ?></strong> Read</div>
                </div>

                <!-- Controls -->
                <div class="controls-row">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="unread">Unread</button>
                    <button class="filter-btn" data-filter="concern">Concerns</button>
                    <button class="filter-btn" data-filter="appointment">Appointments</button>
                    <button class="filter-btn" data-filter="reply">Replies</button>
                    
                    <div class="controls-right">
                        <button class="action-btn btn-mark-all" onclick="markAllAsRead()">✓ Mark All Read</button>
                        <button class="action-btn btn-delete-read" onclick="deleteAllRead()">🗑️ Delete Read</button>
                    </div>
                </div>

                <!-- Bulk Actions Bar -->
                <div class="bulk-actions" id="bulkActions">
                    <span class="selected-count"><span id="selectedCount">0</span> selected</span>
                    <button onclick="markSelectedAsRead()">✓ Mark Read</button>
                    <button onclick="deleteSelected()">🗑️ Delete</button>
                    <button class="btn-cancel" onclick="clearSelection()">✕ Cancel</button>
                </div>

                <!-- Notification List -->
                <div class="notification-list" id="notificationList">
                    <!-- Notifications will be loaded here -->
                </div>

                <!-- Loading Spinner -->
                <div class="loading-spinner" id="loadingSpinner">
                    ⏳ Loading more notifications...
                </div>

                <!-- End of List -->
                <div class="end-of-list" id="endOfList">
                    ✅ You've seen all notifications
                </div>

                <!-- Empty State -->
                <div class="empty-state" id="emptyState" style="display: none;">
                    <div class="icon">🔔</div>
                    <h3>No notifications</h3>
                    <p>You're all caught up!</p>
                </div>
            </div>
        </main>
    </div>


    <script src="../../js/notifications.js"></script>
    <script src="../../js/coach-notifications.js"></script>
</body>
</html>