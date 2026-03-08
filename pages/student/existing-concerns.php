<?php
require_once '../../api/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$user_role = $_SESSION['role'];
$user_name = $_SESSION['name'];

// Get initials for avatar
$words = explode(" ", $user_name);
$initials = "";
foreach ($words as $w) { if(!empty($w)) $initials .= strtoupper($w[0]); }
$display_initials = substr($initials, 0, 2);

// Get all public concerns
$query = "SELECT c.*, u.name as student_name,
          (SELECT COUNT(*) FROM concern_responses WHERE tracking_id = c.tracking_id) as response_count
          FROM concerns c 
          LEFT JOIN users u ON c.student_id = u.student_id 
          WHERE c.is_public = 1 
          ORDER BY c.created_at DESC";
$result = mysqli_query($conn, $query);

// Get categories for filter
$categories_query = "SELECT DISTINCT category FROM concerns WHERE is_public = 1 ORDER BY category";
$categories_result = mysqli_query($conn, $categories_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Existing Concerns - Kasama Support Hub</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/shared-styles.css">
    <link rel="stylesheet" href="../../css/existing-concerns-styles.css">
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
                <span class="header-title">Existing Concerns</span>
            </div>
            <div class="header-right">
                <?php if ($user_role === 'student'): ?>
                    <button class="btn-back" onclick="window.location.href='dashboard.php'">← Back to Dashboard</button>
                <?php else: ?>
                    <button class="btn-back" onclick="window.location.href='../coach/dashboard.php'">← Back to Dashboard</button>
                <?php endif; ?>
                <?php include '../../includes/notification-bell.php'; ?>
                <div class="user-profile">
                    <div class="user-avatar"><?php echo $display_initials; ?></div>
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <div class="concerns-container">
                <div class="page-header">
                    <h1>🌐 Existing Concerns</h1>
                    <p>Browse concerns shared by other students. You might find answers to similar issues!</p>
                </div>
                
                <!-- Info Banner -->
                <div class="info-banner">
                    <span class="icon">💡</span>
                    <div class="text">
                        <h3>Learn from Others</h3>
                        <p>These are concerns that students chose to share publicly. Names are hidden for privacy unless the student opted to show theirs.</p>
                    </div>
                </div>
                
                <!-- Search & Filter -->
                <div class="filter-section">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search concerns..." onkeyup="filterConcerns()">
                    </div>
                    <select class="filter-select" id="categoryFilter" onchange="filterConcerns()">
                        <option value="">All Categories</option>
                        <?php while($cat = mysqli_fetch_assoc($categories_result)): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>"><?php echo htmlspecialchars($cat['category']); ?></option>
                        <?php endwhile; ?>
                    </select>
                    <select class="filter-select" id="statusFilter" onchange="filterConcerns()">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                </div>
                
                <!-- Concerns Grid -->
                <div class="concerns-grid" id="concernsGrid">
                    <?php if($result && mysqli_num_rows($result) > 0): ?>
                        <?php while($concern = mysqli_fetch_assoc($result)): 
                            // Determine display name
                            $display_name = ($concern['is_anonymous'] == 1) ? "Anonymous Student" : $concern['student_name'];
                            $avatar_text = ($concern['is_anonymous'] == 1) ? "?" : strtoupper(substr($concern['student_name'], 0, 1));
                            
                            // Status class
                            $status_class = 'status-' . strtolower(str_replace(' ', '-', $concern['status']));
                            
                            // Time ago
                            $created = new DateTime($concern['created_at']);
                            $now = new DateTime();
                            $diff = $now->diff($created);
                            if ($diff->days > 30) {
                                $time_ago = date('M d, Y', strtotime($concern['created_at']));
                            } elseif ($diff->days > 0) {
                                $time_ago = $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
                            } elseif ($diff->h > 0) {
                                $time_ago = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
                            } else {
                                $time_ago = 'Just now';
                            }
                        ?>
                        <div class="concern-card" 
                             data-category="<?php echo htmlspecialchars($concern['category']); ?>"
                             data-status="<?php echo $concern['status']; ?>"
                             data-search="<?php echo htmlspecialchars(strtolower($concern['subject'] . ' ' . $concern['description'] . ' ' . $concern['category'])); ?>">
                            <div class="card-header">
                                <span class="card-category"><?php echo htmlspecialchars($concern['category']); ?></span>
                                <h3 class="card-title"><?php echo htmlspecialchars($concern['subject']); ?></h3>
                            </div>
                            <div class="card-body">
                                <p class="card-description"><?php echo htmlspecialchars($concern['description']); ?></p>
                                <div class="card-meta">
                                    <div class="meta-left">
                                        <div class="meta-avatar"><?php echo $avatar_text; ?></div>
                                        <span class="meta-name"><?php echo htmlspecialchars($display_name); ?></span>
                                    </div>
                                    <div class="meta-right"><?php echo $time_ago; ?></div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $concern['status']; ?></span>
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <span class="response-count">💬 <?php echo $concern['response_count']; ?> response<?php echo $concern['response_count'] != 1 ? 's' : ''; ?></span>
                                    <a href="../shared/view-public-concern.php?id=<?php echo $concern['tracking_id']; ?>" class="btn-view">View</a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state" style="grid-column: 1 / -1;">
                            <div class="icon">📭</div>
                            <h3>No Shared Concerns Yet</h3>
                            <p>When students choose to share their concerns publicly, they will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>


    <script src="../../js/existing-concerns.js"></script>
</body>
</html>