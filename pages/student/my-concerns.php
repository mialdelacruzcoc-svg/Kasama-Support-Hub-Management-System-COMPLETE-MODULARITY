<?php
require_once '../../api/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../../index.php');
    exit;
}

$student_name = $_SESSION['name'];
$student_id = $_SESSION['student_id'];

$words = explode(" ", $student_name);
$initials = "";
foreach ($words as $w) {
    if (!empty($w))
        $initials .= strtoupper($w[0]);
}
$display_initials = substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Concerns - Kasama Support Hub</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/shared-styles.css">
    <link rel="stylesheet" href="../../css/my-concerns-styles.css">
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
                <a href="../../api/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="../../images/phinma-logo.png" alt="Logo" class="header-logo">
                <span class="header-title">My Concerns</span>
            </div>
            <div class="header-right">
                <button class="btn-back" onclick="window.location.href='dashboard.php'">
                    ← Back
                </button>
                <div class="user-profile">
                    <div class="user-avatar"><?php echo $display_initials; ?></div>
                    <span class="user-name"><?php echo htmlspecialchars($student_name); ?></span>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <div class="concerns-container">
                <h1 class="page-title">📋 My Submitted Concerns</h1>
                
                <div class="stats-bar">
                    <div class="stat-item"><div class="number" id="totalCount">0</div><div class="label">Total</div></div>
                    <div class="stat-item pending"><div class="number" id="pendingCount">0</div><div class="label">Pending</div></div>
                    <div class="stat-item in-progress"><div class="number" id="progressCount">0</div><div class="label">In Progress</div></div>
                    <div class="stat-item resolved"><div class="number" id="resolvedCount">0</div><div class="label">Resolved</div></div>
                </div>
                
                <div class="filter-bar">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="Pending">🟡 Pending</button>
                    <button class="filter-btn" data-filter="In Progress">🔵 In Progress</button>
                    <button class="filter-btn" data-filter="Resolved">🟢 Resolved</button>
                </div>
                
                <div id="concernsList"><div class="empty-state"><p>Loading your concerns...</p></div></div>
            </div>
        </main>
    </div>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>🗑️ Delete Concern?</h3>
            <p>Are you sure you want to delete this concern? This action cannot be undone.</p>
            <input type="hidden" id="deleteTrackingId">
            <div class="modal-buttons">
                <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-confirm-delete" onclick="confirmDelete()">Yes, Delete</button>
            </div>
        </div>
    </div>


    <script src="../../js/my-concerns.js"></script>
</body>
</html>