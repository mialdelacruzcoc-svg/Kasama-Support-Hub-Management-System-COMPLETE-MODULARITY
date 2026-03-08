<?php
require_once '../../api/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Security Check: Siguraduhon nga naka-login ang student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['name'];

// I-fetch ang email automatically gikan sa database base sa user_id
$email_query = "SELECT email FROM users WHERE id = '$user_id'";
$email_result = mysqli_query($conn, $email_query);
$student_data = mysqli_fetch_assoc($email_result);
$student_email = $student_data['email'] ?? 'No email found';

$words = explode(" ", $student_name);
$initials = "";
foreach ($words as $w) { if(!empty($w)) $initials .= strtoupper($w[0]); }
$display_initials = substr($initials, 0, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Concern - Kasama Support Hub</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/shared-styles.css">
    <link rel="stylesheet" href="../../css/submit-concern-form-styles.css">
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
            <div class="nav-right">
                <button class="nav-icon">🔔</button>
                <button class="btn-share">Share</button>
            </div>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="../../images/phinma-logo.png" alt="Logo" class="header-logo">
                <span class="header-title">Submit a Concern</span>
            </div>
            <div class="header-right">
                <button class="btn-back" onclick="window.location.href='dashboard.php'">
                    ← Back to Dashboard
                </button>
                <div class="user-profile">
                    <div class="user-avatar" style="background:#4a7c2c; color:white; display:flex; align-items:center; justify-content:center; width:35px; height:35px; border-radius:50%; font-weight:bold;">
                        <?php echo substr($student_name, 0, 1); ?>
                    </div>
                    <span class="user-name"><?php echo $student_name; ?></span>
                    <span class="dropdown-arrow">▼</span>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <div class="submit-concern-container">
                <div class="concern-form-section">
                    <h1 class="page-title">📝 Submit a Concern</h1>
                    <p class="page-subtitle">Tell us what's on your mind. We're here to help.</p>

                    <form id="concernForm" class="concern-form">
                        <div class="form-section">
                            <h3 class="section-title">Student Information</h3>
                            <div class="form-row">
                                <div class="input-group">
                                    <label>Your Name</label>
                                    <input type="text" value="<?php echo $student_name; ?>" readonly style="background-color: #f9f9f9;">
                                </div>
                                <div class="input-group">
                                    <label>Student ID</label>
                                    <input type="text" value="<?php echo $student_id; ?>" readonly style="background-color: #f9f9f9;">
                                </div>
                            </div>
                            <div class="input-group">
                                <label>Email Address</label>
                                <input type="email" value="<?php echo $student_email; ?>" readonly style="background-color: #f9f9f9;">
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3 class="section-title">Concern Details</h3>
                            <div class="input-group">
                                <label>Category *</label>
                                <select name="category" required>
                                    <option value="">Select a category</option>
                                    <option value="Academic">📚 Academic</option>
                                    <option value="Personal">👤 Personal</option>
                                    <option value="Financial">💰 Financial</option>
                                    <option value="Mental Health">🧠 Mental Health</option>
                                    <option value="Others">📌 Other</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label>Subject *</label>
                                <input type="text" name="subject" placeholder="Brief summary" maxlength="100" required>
                                <span class="char-count">0/100</span>
                            </div>
                            <div class="input-group">
                                <label>Description *</label>
                                <textarea name="description" placeholder="Describe in detail..." rows="8" maxlength="1000" required></textarea>
                                <span class="char-count">0/1000</span>
                            </div>
                            <div class="input-group">
                                <label>Urgency Level *</label>
                                <div class="urgency-buttons">
                                    <label class="urgency-option"><input type="radio" name="urgency" value="Low" required><span class="urgency-badge low">🟢 Low</span></label>
                                    <label class="urgency-option"><input type="radio" name="urgency" value="Medium" required><span class="urgency-badge medium">🟡 Medium</span></label>
                                    <label class="urgency-option"><input type="radio" name="urgency" value="High" required><span class="urgency-badge high">🔴 High</span></label>
                                </div>
                            </div>
                            <div class="input-group">
                                <label class="checkbox-label"><input type="checkbox" name="anonymous" value="1"> Submit anonymously</label>
                            </div>
                        </div>

                        <div style="margin-top: 15px;">
    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
        <input type="checkbox" id="isPublic" name="is_public" value="1">
        <span>🌐 Share this concern publicly (others can learn from it)</span>
    </label>
    <p style="font-size: 12px; color: #888; margin-top: 5px; margin-left: 25px;">
        If checked, your concern will appear in "Existing Concerns" for other students to see. Your name will still be hidden if you chose anonymous.
    </p>
</div>

                        <!-- FILE ATTACHMENT SECTION -->
                        <div class="form-section">
                            <h3 class="section-title">📎 Attachments (Optional)</h3>
                            <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                                You can attach supporting documents. Allowed: Images, PDF, Word, Excel (Max 5MB each)
                            </p>
                            
                            <div class="file-upload-area" id="fileUploadArea">
                                <input type="file" id="attachmentInput" multiple 
                                       accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx"
                                       style="display: none;">
                                <div class="upload-placeholder" onclick="document.getElementById('attachmentInput').click();">
                                    <span style="font-size: 40px;">📁</span>
                                    <p>Click to select files or drag & drop</p>
                                    <small>JPG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX (Max 5MB each)</small>
                                </div>
                            </div>
                            
                            <div id="filePreviewList" class="file-preview-list"></div>
                        </div>
                        <!-- END FILE ATTACHMENT SECTION -->

                        <div class="form-actions">
                            <button type="button" class="btn-secondary" onclick="window.location.href='dashboard.php'">Cancel</button>
                            <button type="submit" id="submitBtn" class="btn-submit">Submit Concern</button>
                        </div>
                    </form>
                </div>

                <div class="concern-help-section">
                    <div class="help-card">
                        <h3>💡 Before You Submit</h3>
                        <ul>
                            <li>Check the FAQ page!</li>
                            <li>Be specific.</li>
                            <li>Choose correct category.</li>
                        </ul>
                    </div>
                    <div class="help-card emergency">
                        <h3>🚨 Emergency?</h3>
                        <p>Contact:</p>
                        <div class="emergency-contact"><strong>Guidance:</strong> (088) 123-4568<br><strong>Crisis:</strong> 1553</div>
                    </div>
                </div>
            </div>
        </main>
    </div>


    <script src="../../js/submit-concern-form.js"></script>
</body>
</html>