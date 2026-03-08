<?php
require_once '../../api/config.php';

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coach') { 
    header('Location: ../../index.php'); 
    exit; 
}

$faqs = mysqli_query($conn, "SELECT * FROM faqs ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage FAQ - Coach Hannah</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/shared-styles.css">
    <link rel="stylesheet" href="../../css/coach-faq-manager-styles.css">
    <link rel="stylesheet" href="../../css/dark-mode.css">
    <script src="../../js/dark-mode.js"></script>
</head>
<body style="background:#f4f7f6; margin: 0; padding: 0;">

    <header class="dashboard-header">
        <div class="header-left">
            <img src="../../images/phinma-logo.png" alt="Logo" class="header-logo">
            <span class="header-title">FAQ Control Center</span>
        </div>
        <div class="header-right">
            <a href="dashboard.php" class="btn-back-header">← Back to Dashboard</a>
        </div>
    </header>

    <div style="padding: 0 20px;">
        <div class="admin-card">
            <h2>➕ Add New FAQ</h2>
            <form id="addFaqForm">
                <input type="hidden" name="action" value="add">
                <div class="input-group">
                    <label>Category</label>
                    <select name="category" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd; margin-bottom:15px;">
                        <option value="Academic">Academic</option>
                        <option value="Enrollment">Enrollment</option>
                        <option value="Financial">Financial</option>
                        <option value="Personal Support">Personal Support</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Question</label>
                    <input type="text" name="question" required placeholder="Unsa ang pangutana?" style="width:100%; padding:12px; border-radius:8px; border:1px solid #ddd; margin-bottom:15px; box-sizing: border-box;">
                </div>
                <div class="input-group">
                    <label>Answer</label>
                    <textarea name="answer" required rows="4" placeholder="I-type ang tubag diri..." style="width:100%; padding:12px; border-radius:8px; border:1px solid #ddd; box-sizing: border-box;"></textarea>
                </div>
                <button type="submit" class="btn-signin" style="background:#4a7c2c; margin-top:15px; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%;">Publish FAQ</button>
            </form>
        </div>

        <div class="admin-card" style="overflow-x: auto;">
            <h2>📋 Current FAQs</h2>
            <table class="faq-table">
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Category</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($faqs) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($faqs)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['question']); ?></td>
                            <td><span style="font-size: 0.85rem; background: #e8f5e9; color: #2e7d32; padding: 4px 8px; border-radius: 4px;"><?php echo $row['category']; ?></span></td>
                            <td><button class="btn-delete" onclick="deleteFaq(<?php echo $row['id']; ?>)">Delete</button></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center; padding: 20px; color: #999;">Walay sulod ang FAQ list.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>


    <script src="../../js/coach-faq-manager.js"></script>
</body>
</html>