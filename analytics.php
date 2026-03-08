<?php
require_once 'api/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    header('Location: index.php');
    exit;
}

$coach_name = $_SESSION['name'];

// Get filter period
$period = $_GET['period'] ?? 'all';
$date_filter = '';
$period_label = 'All Time';

switch ($period) {
    case 'today':
        $date_filter = "AND DATE(created_at) = CURDATE()";
        $period_label = 'Today';
        break;
    case 'week':
        $date_filter = "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $period_label = 'This Week';
        break;
    case 'month':
        $date_filter = "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $period_label = 'This Month';
        break;
    case 'year':
        $date_filter = "AND YEAR(created_at) = YEAR(CURDATE())";
        $period_label = 'This Year';
        break;
}

// Basic Stats
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved
    FROM concerns WHERE 1=1 $date_filter";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $stats_query));

$total = $stats['total'] ?? 0;
$pending = $stats['pending'] ?? 0;
$in_progress = $stats['in_progress'] ?? 0;
$resolved = $stats['resolved'] ?? 0;
$resolution_rate = ($total > 0) ? round(($resolved / $total) * 100) : 0;

// Category breakdown
$cat_query = "SELECT category, COUNT(*) as count FROM concerns WHERE 1=1 $date_filter GROUP BY category ORDER BY count DESC";
$cat_result = mysqli_query($conn, $cat_query);
$categories = [];
while ($row = mysqli_fetch_assoc($cat_result)) {
    $categories[$row['category']] = $row['count'];
}

// Average Response Time (hours)
$response_time_query = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, first_response_at)) as avg_response 
                        FROM concerns WHERE first_response_at IS NOT NULL $date_filter";
$avg_response = mysqli_fetch_assoc(mysqli_query($conn, $response_time_query))['avg_response'];
$avg_response = $avg_response ? round($avg_response, 1) : '--';

// Average Resolution Time (hours)
$resolution_time_query = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_resolution 
                          FROM concerns WHERE resolved_at IS NOT NULL $date_filter";
$avg_resolution = mysqli_fetch_assoc(mysqli_query($conn, $resolution_time_query))['avg_resolution'];
$avg_resolution = $avg_resolution ? round($avg_resolution, 1) : '--';

// Concerns over last 7 days (for trend chart)
$trend_query = "SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM concerns 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at) 
                ORDER BY date ASC";
$trend_result = mysqli_query($conn, $trend_query);
$trend_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $trend_data[$date] = 0;
}
while ($row = mysqli_fetch_assoc($trend_result)) {
    $trend_data[$row['date']] = $row['count'];
}

// Week-over-week comparison
$this_week = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as count FROM concerns WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"))['count'];
$last_week = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as count FROM concerns WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND created_at < DATE_SUB(CURDATE(), INTERVAL 7 DAY)"))['count'];
$week_change = $last_week > 0 ? round((($this_week - $last_week) / $last_week) * 100) : 0;

// Students needing attention (3+ unresolved concerns)
$attention_query = "SELECT u.name, c.student_id, COUNT(*) as concern_count, MAX(c.created_at) as last_concern
                    FROM concerns c
                    LEFT JOIN users u ON c.student_id = u.student_id
                    WHERE c.status IN ('Pending', 'In Progress')
                    GROUP BY c.student_id
                    HAVING concern_count >= 2
                    ORDER BY concern_count DESC, last_concern DESC
                    LIMIT 10";
$attention_result = mysqli_query($conn, $attention_query);

// Top 3 concern categories
$top3_query = "SELECT category, COUNT(*) as count FROM concerns WHERE 1=1 $date_filter GROUP BY category ORDER BY count DESC LIMIT 3";
$top3_result = mysqli_query($conn, $top3_query);
$top3 = [];
while ($row = mysqli_fetch_assoc($top3_result)) {
    $top3[] = $row;
}

// Urgency breakdown
$urgency_query = "SELECT urgency, COUNT(*) as count FROM concerns WHERE 1=1 $date_filter GROUP BY urgency";
$urgency_result = mysqli_query($conn, $urgency_query);
$urgency_data = [];
while ($row = mysqli_fetch_assoc($urgency_result)) {
    $urgency_data[$row['urgency']] = $row['count'];
}

// Concerns by hour of day
$hourly_query = "SELECT HOUR(created_at) as hour, COUNT(*) as count FROM concerns WHERE 1=1 $date_filter GROUP BY HOUR(created_at) ORDER BY hour";
$hourly_result = mysqli_query($conn, $hourly_query);
$hourly_data = array_fill(0, 24, 0);
while ($row = mysqli_fetch_assoc($hourly_result)) {
    $hourly_data[$row['hour']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Kasama Support Hub</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/shared-styles.css">
    <link rel="stylesheet" href="css/analytics-styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/dark-mode.css">
    <script src="js/dark-mode.js"></script>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-content">
            <div class="nav-left"><span class="nav-title">Kasama Support Hub</span></div>
            <div class="nav-right">
                <a href="api/logout.php" style="color:white; text-decoration:none; font-weight:bold;">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="images/phinma-logo.png" alt="Logo" class="header-logo">
                <span class="header-title">Analytics Dashboard</span>
            </div>
            <div class="header-right">
                <button class="btn-back" onclick="window.location.href='pages/coach/dashboard.php'">← Back to Dashboard</button>
                <?php include 'includes/profile-dropdown.php'; ?>
            </div>
        </header>

        <main class="dashboard-main">
            <div class="analytics-container">
                <!-- Header -->
                <div class="analytics-header">
                    <div>
                        <h1>📊 Analytics & Insights</h1>
                        <p>Comprehensive analysis of student concerns and support metrics</p>
                    </div>
                    <div class="header-controls">
                        <select class="period-select" onchange="changePeriod(this.value)">
                            <option value="all" <?php if ($period == 'all')
    echo 'selected'; ?>>All Time</option>
                            <option value="today" <?php if ($period == 'today')
    echo 'selected'; ?>>Today</option>
                            <option value="week" <?php if ($period == 'week')
    echo 'selected'; ?>>This Week</option>
                            <option value="month" <?php if ($period == 'month')
    echo 'selected'; ?>>This Month</option>
                            <option value="year" <?php if ($period == 'year')
    echo 'selected'; ?>>This Year</option>
                        </select>
                        <button class="btn-export" onclick="exportReport()">📥 Export Report</button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon">📊</div>
                        <div class="number"><?php echo $total; ?></div>
                        <div class="label">Total Concerns</div>
                        <div class="change <?php echo $week_change >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $week_change >= 0 ? '↑' : '↓'; ?> <?php echo abs($week_change); ?>% vs last week
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">⏳</div>
                        <div class="number orange"><?php echo $pending; ?></div>
                        <div class="label">Pending</div>
                        <div class="change neutral">Needs attention</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">🔄</div>
                        <div class="number blue"><?php echo $in_progress; ?></div>
                        <div class="label">In Progress</div>
                        <div class="change neutral">Being addressed</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">✅</div>
                        <div class="number green"><?php echo $resolved; ?></div>
                        <div class="label">Resolved</div>
                        <div class="change positive"><?php echo $resolution_rate; ?>% resolution rate</div>
                    </div>
                </div>

                <!-- Time Metrics -->
                <div class="metrics-row">
                    <div class="metric-card">
                        <div class="value"><?php echo $avg_response; ?></div>
                        <div class="unit">hours</div>
                        <div class="label">Avg. Response Time</div>
                    </div>
                    <div class="metric-card">
                        <div class="value"><?php echo $avg_resolution; ?></div>
                        <div class="unit">hours</div>
                        <div class="label">Avg. Resolution Time</div>
                    </div>
                    <div class="metric-card">
                        <div class="value"><?php echo $this_week; ?></div>
                        <div class="unit">concerns</div>
                        <div class="label">This Week</div>
                    </div>
                    <div class="metric-card">
                        <div class="value"><?php echo $resolution_rate; ?>%</div>
                        <div class="unit"></div>
                        <div class="label">Resolution Rate</div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="charts-grid">
                    <!-- Trend Chart -->
                    <div class="chart-card full-width">
                        <h3>📈 Concerns Trend (Last 7 Days)</h3>
                        <div class="chart-container tall">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Category Chart -->
                    <div class="chart-card">
                        <h3>📁 Concerns by Category</h3>
                        <div class="chart-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Status Chart -->
                    <div class="chart-card">
                        <h3>📊 Status Distribution</h3>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Urgency Chart -->
                    <div class="chart-card">
                        <h3>🚨 Urgency Levels</h3>
                        <div class="chart-container">
                            <canvas id="urgencyChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Hourly Distribution -->
                    <div class="chart-card">
                        <h3>🕐 Submissions by Hour</h3>
                        <div class="chart-container">
                            <canvas id="hourlyChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Lists -->
                <div class="lists-grid">
                    <!-- Top Categories -->
                    <div class="list-card">
                        <h3>🔥 Top Concern Categories</h3>
                        <?php if (count($top3) > 0): ?>
                            <?php foreach ($top3 as $index => $item):
        $rank_class = $index === 0 ? 'gold' : ($index === 1 ? 'silver' : 'bronze');
?>
                            <div class="top-item">
                                <div class="top-rank <?php echo $rank_class; ?>"><?php echo $index + 1; ?></div>
                                <div class="top-content">
                                    <div class="name"><?php echo htmlspecialchars($item['category']); ?></div>
                                    <div class="count"><?php echo $item['count']; ?> concern<?php echo $item['count'] != 1 ? 's' : ''; ?></div>
                                </div>
                            </div>
                            <?php
    endforeach; ?>
                        <?php
else: ?>
                            <div class="empty-state">No data available</div>
                        <?php
endif; ?>
                    </div>

                    <!-- Students Needing Attention -->
                    <div class="list-card">
                        <h3>⚠️ Students Needing Attention</h3>
                        <?php if ($attention_result && mysqli_num_rows($attention_result) > 0): ?>
                            <?php while ($student = mysqli_fetch_assoc($attention_result)): ?>
                            <div class="attention-item">
                                <div class="attention-info">
                                    <div class="attention-avatar"><?php echo strtoupper(substr($student['name'] ?? 'S', 0, 1)); ?></div>
                                    <div>
                                        <div class="attention-name"><?php echo htmlspecialchars($student['name'] ?? 'Unknown'); ?></div>
                                        <div class="attention-id"><?php echo $student['student_id']; ?></div>
                                    </div>
                                </div>
                                <div class="attention-badge"><?php echo $student['concern_count']; ?> unresolved</div>
                            </div>
                            <?php
    endwhile; ?>
                        <?php
else: ?>
                            <div class="empty-state">
                                <p>🎉 No students with multiple unresolved concerns!</p>
                            </div>
                        <?php
endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Period filter
    function changePeriod(period) {
        window.location.href = 'analytics.php?period=' + period;
    }

    // Export Report
function exportReport() {
    const period = document.querySelector('.period-select').value;
    window.location.href = 'api/export-report.php?period=' + period;
}

    // Chart.js defaults
    Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';

    // Trend Chart (Line)
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(function ($d) {
    return date('M d', strtotime($d)); }, array_keys($trend_data))); ?>,
            datasets: [{
                label: 'Concerns',
                data: <?php echo json_encode(array_values($trend_data)); ?>,
                borderColor: '#4a7c2c',
                backgroundColor: 'rgba(74, 124, 44, 0.1)',
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#4a7c2c',
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // Category Chart (Bar)
    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($categories)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($categories)); ?>,
                backgroundColor: ['#4a7c2c', '#1565c0', '#ef6c00', '#c62828', '#6a1b9a']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // Status Chart (Doughnut)
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'In Progress', 'Resolved'],
            datasets: [{
                data: [<?php echo $pending; ?>, <?php echo $in_progress; ?>, <?php echo $resolved; ?>],
                backgroundColor: ['#ef6c00', '#1565c0', '#2e7d32']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Urgency Chart (Pie)
    new Chart(document.getElementById('urgencyChart'), {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_keys($urgency_data)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($urgency_data)); ?>,
                backgroundColor: ['#2e7d32', '#ef6c00', '#c62828']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Hourly Chart (Bar)
    new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_map(function ($h) {
    return date('g A', strtotime("$h:00")); }, range(0, 23))); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($hourly_data)); ?>,
                backgroundColor: '#1565c0'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
    </script>
</body>
</html>