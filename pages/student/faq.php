<?php
require_once '../../api/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$student_name = $_SESSION['name'] ?? 'Student';
$display_initials = substr(strtoupper($student_name), 0, 2);

// Get all categories for filter
$categories_query = "SELECT DISTINCT category FROM faqs WHERE category IS NOT NULL AND category != '' ORDER BY category";
$categories_result = mysqli_query($conn, $categories_query);

// Fetch FAQs
$query = "SELECT * FROM faqs ORDER BY is_pinned DESC, view_count DESC, created_at DESC";
$faqs_result = mysqli_query($conn, $query);
$faqs_array = [];
while ($row = mysqli_fetch_assoc($faqs_result)) {
    $faqs_array[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Kasama Support Hub</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/shared-styles.css">
    <link rel="stylesheet" href="../../css/faq-styles.css">
    <link rel="stylesheet" href="../../css/dark-mode.css">
    <script src="../../js/dark-mode.js"></script>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-content">
            <div class="nav-left"><span class="nav-title">Kasama Support Hub</span></div>
            <div class="nav-right">
                <a href="../../api/logout.php" style="color:white; text-decoration:none; font-weight:bold; font-size:13px;">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="../../images/phinma-logo.png" alt="Logo" class="header-logo" style="width:36px; height:36px;">
                <span class="header-title" style="font-size:15px; font-weight:600;">Help Center</span>
            </div>
            <div class="header-right">
                <button class="btn-back" onclick="window.location.href='dashboard.php'">← Back</button>
            </div>
        </header>

        <main class="faq-container">
            <div class="page-header">
                <h1>❓ Frequently Asked Questions</h1>
                <p>Find answers to common questions. Search by keyword or filter by category.</p>
            </div>
            
            <!-- Search Section -->
            <div class="search-section">
                <div class="search-input-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="faqSearch" class="search-input" placeholder="Search FAQs..." autocomplete="off">
                </div>
                <div class="search-results-count" id="resultsCount"></div>
                
                <!-- Category Filter -->
                <div class="category-filter">
                    <button class="category-btn active" data-category="all">All</button>
                    <?php
mysqli_data_seek($categories_result, 0);
while ($cat = mysqli_fetch_assoc($categories_result)):
?>
                    <button class="category-btn" data-category="<?php echo htmlspecialchars($cat['category']); ?>">
                        <?php echo htmlspecialchars($cat['category']); ?>
                    </button>
                    <?php
endwhile; ?>
                </div>
            </div>
            
            <!-- FAQ List -->
            <div class="faq-list" id="faqList">
                <?php if (count($faqs_array) > 0): ?>
                    <?php foreach ($faqs_array as $row): ?>
                        <div class="faq-item" data-id="<?php echo $row['id']; ?>" data-category="<?php echo htmlspecialchars($row['category']); ?>" data-question="<?php echo htmlspecialchars(strtolower($row['question'])); ?>" data-answer="<?php echo htmlspecialchars(strtolower($row['answer'])); ?>">
                            <button class="faq-question" onclick="toggleFaq(this, <?php echo $row['id']; ?>)">
                                <span class="faq-question-text"><?php echo htmlspecialchars($row['question']); ?></span>
                                <div class="faq-meta">
                                    <span class="faq-category-tag"><?php echo htmlspecialchars($row['category']); ?></span>
                                    <span class="faq-toggle">+</span>
                                </div>
                            </button>
                            <div class="faq-answer">
                                <?php echo nl2br(htmlspecialchars($row['answer'])); ?>
                            </div>
                        </div>
                    <?php
    endforeach; ?>
                <?php
else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <h3>No FAQs Available</h3>
                        <p>Check back later for answers to common questions.</p>
                    </div>
                <?php
endif; ?>
            </div>
            
            <!-- No Results State -->
            <div class="empty-state" id="noResults" style="display: none;">
                <div class="empty-state-icon">🔍</div>
                <h3>No Results Found</h3>
                <p>Try different keywords or select a different category.</p>
            </div>
        </main>
    </div>

    <script>
    // Store total FAQ count
    const totalFaqs = <?php echo count($faqs_array); ?>;
    let currentCategory = 'all';
    
    // Toggle FAQ accordion
    function toggleFaq(button, faqId) {
        const item = button.parentElement;
        const wasActive = item.classList.contains('active');
        
        // Close all others
        document.querySelectorAll('.faq-item').forEach(el => el.classList.remove('active'));
        
        // Toggle current
        if (!wasActive) {
            item.classList.add('active');
            
            // Track view (only when opening)
            trackFaqView(faqId);
        }
    }
    
    // Track FAQ view count (FR3 requirement)
    async function trackFaqView(faqId) {
        try {
            const formData = new FormData();
            formData.append('faq_id', faqId);
            await fetch('../../api/track-faq-view.php', { method: 'POST', body: formData });
        } catch (e) {
            console.log('View tracking failed');
        }
    }
    
    // Search functionality
    const searchInput = document.getElementById('faqSearch');
    const resultsCount = document.getElementById('resultsCount');
    const noResults = document.getElementById('noResults');
    const faqList = document.getElementById('faqList');
    
    searchInput.addEventListener('input', filterFaqs);
    
    // Category filter
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentCategory = this.dataset.category;
            filterFaqs();
        });
    });
    
    function filterFaqs() {
        const query = searchInput.value.toLowerCase().trim();
        const items = document.querySelectorAll('.faq-item');
        let visibleCount = 0;
        
        items.forEach(item => {
            const question = item.dataset.question || '';
            const answer = item.dataset.answer || '';
            const category = item.dataset.category || '';
            
            // Check category match
            const categoryMatch = (currentCategory === 'all' || category === currentCategory);
            
            // Check search match
            const searchMatch = (query === '' || question.includes(query) || answer.includes(query));
            
            if (categoryMatch && searchMatch) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
                item.classList.remove('active');
            }
        });
        
        // Update results count
        if (query || currentCategory !== 'all') {
            resultsCount.textContent = `Showing ${visibleCount} of ${totalFaqs} FAQs`;
        } else {
            resultsCount.textContent = '';
        }
        
        // Show/hide no results message
        if (visibleCount === 0 && totalFaqs > 0) {
            noResults.style.display = 'block';
            faqList.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            faqList.style.display = 'flex';
        }
    }
    </script>
</body>
</html>