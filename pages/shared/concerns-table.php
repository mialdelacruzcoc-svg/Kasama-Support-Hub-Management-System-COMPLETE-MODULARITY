<?php
require_once '../../api/config.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    header('Location: ../../index.php');
    exit;
}

$coach_name = $_SESSION['name'] ?? 'Coach';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Concerns - Kasama Support Hub</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/shared-styles.css">
    <link rel="stylesheet" href="../../css/concerns-table-styles.css">
    <link rel="stylesheet" href="../../css/dark-mode.css">
    <script src="../../js/dark-mode.js"></script>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-content">
            <div class="nav-left">
                <span class="hamburger">☰</span>
                <span class="nav-title">Kasama Support Hub</span>
            </div>
            <div class="nav-right" style="display: flex; align-items: center; gap: 15px;">
                <a href="../../api/logout.php" style="color: #ffffff; text-decoration: none; font-size: 14px; font-weight: bold;">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="../../images/phinma-logo.png" alt="Logo" class="header-logo">
                <span class="header-title">All Student Concerns</span>
            </div>
            <div class="header-right">
                <button class="btn-back" onclick="window.location.href='../coach/dashboard.php'">← Back to Dashboard</button>
                
                <!-- NOTIFICATION BELL -->
                <div class="notif-wrapper">
                    <button class="notif-bell" id="notifBell" type="button">
                        🔔
                        <span class="notif-badge hidden" id="notifBadge">0</span>
                    </button>
                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-header">
                            <h4>🔔 Notifications</h4>
                            <button class="mark-read-btn" id="markAllBtn" type="button">Mark all read</button>
                        </div>
                        <div class="notif-list" id="notifList">
                            <div class="notif-empty">Loading...</div>
                        </div>
                    </div>
                </div>
                <!-- END NOTIFICATION BELL -->
                
                <?php include '../../includes/profile-dropdown.php'; ?>
            </div>
        </header>

        <main class="dashboard-main">
            <h1 class="page-title">📋 All Concerns (<span id="concernCount">0</span>)</h1>

            <div class="table-controls">
                <div class="filter-group">
                    <select class="filter-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="in-progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                    </select>
                    
                    <select class="filter-select" id="categoryFilter">
                        <option value="">All Categories</option>
                        <option value="academic">Academic</option>
                        <option value="personal">Personal</option>
                        <option value="financial">Financial</option>
                        <option value="mental-health">Mental Health</option>
                        <option value="facilities">Facilities</option>
                    </select>
                    
                    <select class="filter-select" id="sortBy">
    <option value="date-desc">Newest First</option>
    <option value="date-asc">Oldest First</option>
    <option value="urgency">By Urgency</option>
</select>

<select class="filter-select" id="yearLevelFilter">
    <option value="">All Year Levels</option>
    <option value="1st Year">1st Year</option>
    <option value="2nd Year">2nd Year</option>
    <option value="3rd Year">3rd Year</option>
    <option value="4th Year">4th Year</option>
</select>

<select class="filter-select" id="urgencyFilter">
    <option value="">All Urgency Levels</option>
    <option value="Low">Low</option>
    <option value="Medium">Medium</option>
    <option value="High">High</option>
</select>
                </div>
                
                <input type="text" class="search-input" id="searchInput" placeholder="🔍 Search by ID, name, or subject..." style="max-width: 400px;">
            </div>

            <div class="concerns-table">
                <table>
                    <thead>
                        <tr>
                            <th>Concern ID</th>
<th>Student Name</th>
<th>Student ID</th>
<th>Year Level</th>
<th>Subject</th>
<th>Category</th>
<th>Status</th>
<th>Date Submitted</th>
<th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody id="concernsTableBody">
                        <tr><td colspan="8" style="text-align:center; padding:30px;">Loading concerns...</td></tr>
                    </tbody>
                </table>
                
                <div id="noResults" class="no-results" style="display: none;">
                    <p>No concerns found matching your filters.</p>
                </div>
            </div>

            <div class="pagination" id="pagination">
                <button id="prevBtn" onclick="changePage(-1)">← Previous</button>
                <span id="pageInfo">Page 1 of 1</span>
                <button id="nextBtn" onclick="changePage(1)">Next →</button>
            </div>
        </main>
    </div>


    <script src="../../js/notifications.js"></script>
    <script src="../../js/concerns-table.js"></script>
</body>
</html>