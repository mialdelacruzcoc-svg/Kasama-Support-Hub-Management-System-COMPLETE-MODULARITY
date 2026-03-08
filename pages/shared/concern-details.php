<?php
require_once '../../api/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$tracking_id = mysqli_real_escape_string($conn, $_GET['id'] ?? '');
$user_role = $_SESSION['role'];
$user_name = $_SESSION['name'];
$user_id = $_SESSION['user_id'];

// Get initials
$words = explode(" ", $user_name);
$initials = "";
foreach ($words as $w) { if(!empty($w)) $initials .= strtoupper($w[0]); }
$display_initials = substr($initials, 0, 2);

$query = "SELECT c.*, u.name as real_name, u.email as student_email 
          FROM concerns c 
          LEFT JOIN users u ON c.student_id = u.student_id 
          WHERE c.tracking_id = '$tracking_id'";

$result = mysqli_query($conn, $query);
$concern = mysqli_fetch_assoc($result);

if (!$concern) {
    $redirect = ($user_role === 'coach') ? 'concerns-table.php' : '../student/dashboard.php';
    echo "<script>alert('Concern not found!'); window.location.href='$redirect';</script>";
    exit;
}

// Check if student owns this concern (for reply permission)
$can_reply = false;
if ($user_role === 'student' && $concern['student_id'] === $_SESSION['student_id']) {
    $can_reply = true;
}

// Fetch attachments
$attachments_query = "SELECT * FROM concern_attachments WHERE tracking_id = '$tracking_id' ORDER BY uploaded_at ASC";
$attachments_result = mysqli_query($conn, $attachments_query);
$attachments = [];
while ($att = mysqli_fetch_assoc($attachments_result)) {
    $attachments[] = $att;
}
$attachment_count = count($attachments);

// Fetch responses
$responses_query = "SELECT * FROM concern_responses WHERE tracking_id = '$tracking_id' ORDER BY created_at ASC";
$responses_result = mysqli_query($conn, $responses_query);
$responses = [];
if ($responses_result) {
    while ($resp = mysqli_fetch_assoc($responses_result)) {
        $responses[] = $resp;
    }
}

$display_name = ($concern['is_anonymous'] == 1) ? "Anonymous Student" : $concern['real_name'];
$display_id = ($concern['is_anonymous'] == 1) ? "---" : $concern['student_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Concern Details - Kasama Support Hub</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/shared-styles.css">
    <link rel="stylesheet" href="../../css/concern-details-styles.css">
    <link rel="stylesheet" href="../../css/dark-mode.css">
    <script src="../../js/dark-mode.js"></script>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-content">
            <div class="nav-left"><span class="nav-title">Kasama Support Hub</span></div>
            <div class="nav-right"><a href="../../api/logout.php" style="color:white; text-decoration:none; font-weight:bold;">Logout</a></div>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="../../images/phinma-logo.png" alt="Logo" class="header-logo">
                <span class="header-title">Concern Details</span>
            </div>
            <div class="header-right">
                <?php if ($user_role === 'coach'): ?>
                    <button class="btn-back" onclick="window.location.href='concerns-table.php'">← Back to Table</button>
                <?php else: ?>
                    <button class="btn-back" onclick="window.location.href='../student/dashboard.php'">← Back to Dashboard</button>
                <?php endif; ?>
                <div class="user-profile">
                    <div class="user-avatar"><?php echo $display_initials; ?></div>
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <div class="concern-detail-container">
                <div class="concern-header">
                    <div class="concern-id-badge"><?php echo $concern['tracking_id']; ?></div>
                    <h1 style="font-size: 24px; color: #1a1a1a;"><?php echo htmlspecialchars($concern['subject']); ?></h1>
                    
                    <div class="concern-meta">
                        <div class="meta-item">
                            <span class="meta-label">Student</span>
                            <span class="meta-value"><?php echo $display_name; ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Student ID</span>
                            <span class="meta-value"><?php echo $display_id; ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Category</span>
                            <span class="meta-value"><?php echo $concern['category']; ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Status</span>
                            <?php 
                            $status_class = 'status-' . strtolower(str_replace(' ', '-', $concern['status']));
                            ?>
                            <span class="meta-value <?php echo $status_class; ?>" id="statusBadge"><?php echo $concern['status']; ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Urgency</span>
                            <span class="meta-value"><?php echo $concern['urgency']; ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Submitted</span>
                            <span class="meta-value"><?php echo date('M d, Y h:i A', strtotime($concern['created_at'])); ?></span>
                        </div>
                    </div>
                </div>

                <div class="concern-content">
                    <div class="concern-main">
                        <!-- Description -->
                        <div class="concern-card">
                            <h2 class="card-title">📝 Concern Description</h2>
                            <div class="concern-description"><?php echo nl2br(htmlspecialchars($concern['description'])); ?></div>
                        </div>

                        <!-- Attachments -->
                        <div class="concern-card">
                            <h2 class="card-title">📎 Attachments <?php if ($attachment_count > 0) echo "($attachment_count)"; ?></h2>
                            <?php if ($attachment_count > 0): ?>
                                <div class="attachments-list">
                                    <?php foreach ($attachments as $att): 
                                        $icons = ['pdf'=>'📄','doc'=>'📝','docx'=>'📝','xls'=>'📊','xlsx'=>'📊','jpg'=>'🖼️','jpeg'=>'🖼️','png'=>'🖼️','gif'=>'🖼️'];
                                        $icon = $icons[$att['file_extension']] ?? '📎';
                                        $size = $att['file_size'];
                                        $size_formatted = $size >= 1048576 ? round($size / 1048576, 2) . ' MB' : round($size / 1024, 2) . ' KB';
                                    ?>
                                    <div class="attachment-item" onclick="window.open('<?php echo $att['file_path']; ?>', '_blank')">
                                        <div class="attachment-info">
                                            <span class="attachment-icon"><?php echo $icon; ?></span>
                                            <div>
                                                <div class="attachment-name"><?php echo htmlspecialchars($att['original_name']); ?></div>
                                                <div class="attachment-meta"><?php echo $size_formatted; ?> • <?php echo date('M d, Y', strtotime($att['uploaded_at'])); ?></div>
                                            </div>
                                        </div>
                                        <div onclick="event.stopPropagation();">
                                            <a href="<?php echo $att['file_path']; ?>" download class="btn-download">⬇️ Download</a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p style="color: #888; font-style: italic;">No attachments for this concern.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Conversation Thread -->
                        <div class="concern-card">
                            <h2 class="card-title">💬 Conversation Thread</h2>
                            <div class="chat-container" id="chatContainer">
                                <?php if (count($responses) > 0): ?>
                                    <?php foreach ($responses as $resp): 
                                        $is_coach = ($resp['sender_role'] ?? 'coach') === 'coach';
                                        $msg_class = $is_coach ? 'coach' : 'student';
                                        $avatar_text = strtoupper(substr($resp['responder_name'], 0, 2));
                                        if ($resp['responder_name'] === 'Anonymous Student') $avatar_text = '?';
                                        $sender_icon = $is_coach ? '🧑‍🏫' : '🧑‍🎓';
                                    ?>
                                    <div class="chat-message <?php echo $msg_class; ?>">
                                        <?php if ($is_coach): ?>
                                        <div class="chat-avatar"><?php echo $avatar_text; ?></div>
                                        <?php endif; ?>
                                        <div class="chat-bubble">
                                            <div class="chat-sender">
                                                <?php echo $sender_icon; ?> <?php echo htmlspecialchars($resp['responder_name']); ?>
                                            </div>
                                            <div class="chat-text"><?php echo nl2br(htmlspecialchars($resp['message'])); ?></div>
                                            <div class="chat-time"><?php echo date('M d, Y - h:i A', strtotime($resp['created_at'])); ?></div>
                                        </div>
                                        <?php if (!$is_coach): ?>
                                        <div class="chat-avatar"><?php echo $avatar_text; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-messages">
                                        <div class="icon">💬</div>
                                        <p>No messages yet. <?php echo $user_role === 'coach' ? 'Be the first to reply!' : 'Waiting for coach response.'; ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Reply Form -->
                        <?php if ($user_role === 'coach'): ?>
                        <!-- COACH REPLY FORM -->
                        <!-- COACH REPLY FORM -->
<div class="response-form">
    <h2 class="card-title">✏️ Send Reply</h2>
    <textarea placeholder="Write your response to the student..." id="responseMessage" required></textarea>

    <!-- ATTACHMENT UPLOAD FOR COACH (NEW) -->
    <div style="margin-bottom: 15px;">
        <label style="display: block; font-weight: 600; font-size: 13px; color: #444; margin-bottom: 8px;">
            📎 Attach a File <span style="font-weight: 400; color: #888;">(Optional — JPG, PNG, PDF, DOC, DOCX, XLS, XLSX · Max 5MB)</span>
        </label>
        <input type="file" id="coachAttachment" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx"
            style="width: 100%; padding: 8px; border: 1.5px dashed #b0c4b1; border-radius: 8px; background: #f8fdf8; font-size: 13px; cursor: pointer;">
        <div id="coachAttachmentPreview" style="display:none; margin-top: 8px; padding: 8px 12px; background: #e8f5e9; border-radius: 6px; font-size: 13px; color: #2e7d32; display: flex; align-items: center; gap: 8px;">
            <span>📄</span>
            <span id="coachAttachmentFileName"></span>
            <span id="coachAttachmentFileSize" style="color: #888;"></span>
            <button onclick="clearCoachAttachment()" style="margin-left: auto; background: none; border: none; color: #c62828; cursor: pointer; font-size: 13px;">✕ Remove</button>
        </div>
        <div id="coachAttachmentError" style="display:none; margin-top: 6px; font-size: 12px; color: #c62828;"></div>
    </div>

    <div style="margin-bottom: 15px;">
        <label style="font-weight: 600; margin-bottom: 8px; display: block;">Update Status:</label>
        <select id="statusUpdate" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ddd;">
            <option value="Pending" <?php if($concern['status']=='Pending') echo 'selected'; ?>>Pending</option>
            <option value="In Progress" <?php if($concern['status']=='In Progress') echo 'selected'; ?>>In Progress</option>
            <option value="Resolved" <?php if($concern['status']=='Resolved') echo 'selected'; ?>>Resolved</option>
        </select>
    </div>
    <button class="btn-submit" id="coachSubmitBtn" onclick="submitCoachResponse()">📤 Send Response</button>
</div>
                        <!-- STUDENT REPLY FORM -->
                        <?php endif; ?>

                        <?php if ($can_reply): ?>
                        <div class="response-form">
                            <h2 class="card-title">💬 Add Follow-up</h2>

                            <?php if ($concern['status'] === 'Resolved'): ?>
                            <div class="reopened-notice">
                                <span class="icon">⚠️</span>
                                <span class="text">This concern is marked as <strong>Resolved</strong>. Sending a follow-up will reopen it for further review.</span>
                            </div>
                            <?php endif; ?>

                            <textarea id="studentMessage" placeholder="Add additional information or a follow-up message..." required></textarea>

                            <div style="margin-bottom: 15px;">
                                <label style="display: block; font-weight: 600; font-size: 13px; color: #444; margin-bottom: 8px;">
                                    📎 Attach a File <span style="font-weight: 400; color: #888;">(Optional — JPG, PNG, PDF, DOC, DOCX, XLS, XLSX · Max 5MB)</span>
                                </label>
                                <input type="file" id="followupAttachment" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx"
                                    style="width: 100%; padding: 8px; border: 1.5px dashed #b0c4b1; border-radius: 8px; background: #f8fdf8; font-size: 13px; cursor: pointer;">
                                <div id="attachmentPreview" style="display:none; margin-top: 8px; padding: 8px 12px; background: #e8f5e9; border-radius: 6px; font-size: 13px; color: #2e7d32; align-items: center; gap: 8px;">
                                    <span>📄</span>
                                    <span id="attachmentFileName"></span>
                                    <span id="attachmentFileSize" style="color: #888;"></span>
                                    <button onclick="clearAttachment()" style="margin-left: auto; background: none; border: none; color: #c62828; cursor: pointer; font-size: 13px;">✕ Remove</button>
                                </div>
                                <div id="attachmentError" style="display:none; margin-top: 6px; font-size: 12px; color: #c62828;"></div>
                            </div>

                            <button class="btn-submit" id="studentSubmitBtn" onclick="submitStudentReply()">💬 Send Follow-up</button>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar -->
                    <div class="concern-sidebar">
                        <?php if ($user_role === 'coach'): ?>
                        <div class="info-card">
                            <h3>📧 Student Contact</h3>
                            <p style="font-size:14px; margin-top:10px;">
                                <?php echo ($concern['is_anonymous'] == 1) ? "Contact hidden (Anonymous)" : htmlspecialchars($concern['student_email']); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        <div class="info-card">
                            <h3>📊 Quick Stats</h3>
                            <p style="font-size:14px; margin-top:10px;">
                                <strong>Messages:</strong> <?php echo count($responses); ?><br>
                                <strong>Attachments:</strong> <?php echo $attachment_count; ?>
                            </p>
                        </div>
                        <div class="info-card">
                            <h3>📅 Timeline</h3>
                            <p style="font-size:13px; margin-top:10px; line-height: 1.8;">
                                <strong>Submitted:</strong><br><?php echo date('M d, Y h:i A', strtotime($concern['created_at'])); ?><br><br>
                                <?php if ($concern['first_response_at']): ?>
                                <strong>First Response:</strong><br><?php echo date('M d, Y h:i A', strtotime($concern['first_response_at'])); ?><br><br>
                                <?php endif; ?>
                                <?php if ($concern['resolved_at'] && $concern['status'] === 'Resolved'): ?>
                                <strong>Resolved:</strong><br><?php echo date('M d, Y h:i A', strtotime($concern['resolved_at'])); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Scroll chat to bottom
    document.addEventListener('DOMContentLoaded', function() {
        var chatContainer = document.getElementById('chatContainer');
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    });

    // Coach submit response
    // Coach submit response
function submitCoachResponse() {
    var message = document.getElementById('responseMessage').value.trim();
    var status  = document.getElementById('statusUpdate').value;
    var btn     = document.getElementById('coachSubmitBtn');
    var fileInput = document.getElementById('coachAttachment');

    if (!message) {
        alert('Please write a response message.');
        return;
    }

    // Validate file if one is selected
    var file = fileInput.files[0];
    if (file) {
        var allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        var maxSize = 5 * 1024 * 1024; // 5MB

        if (!allowedTypes.includes(file.type)) {
            document.getElementById('coachAttachmentError').style.display = 'block';
            document.getElementById('coachAttachmentError').textContent = '❌ File type not allowed. Use JPG, PNG, PDF, DOC, DOCX, XLS, or XLSX.';
            return;
        }
        if (file.size > maxSize) {
            document.getElementById('coachAttachmentError').style.display = 'block';
            document.getElementById('coachAttachmentError').textContent = '❌ File is too large. Maximum size is 5MB.';
            return;
        }
    }

    btn.disabled = true;
    btn.textContent = 'Sending...';

    var formData = new FormData();
    formData.append('tracking_id', '<?php echo $tracking_id; ?>');
    formData.append('message', message);
    formData.append('status', status);
    if (file) {
        formData.append('attachment', file);
    }

    fetch('../../api/submit-response.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                var msg = '✅ Response sent successfully!';
                if (data.attachment_saved) {
                    msg += '\n📎 Attachment uploaded successfully.';
                }
                if (data.attachment_error) {
                    msg += '\n⚠️ Note: ' + data.attachment_error;
                }
                alert(msg);
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
                btn.disabled = false;
                btn.textContent = '📤 Send Response';
            }
        })
        .catch(err => {
            alert('❌ Connection error');
            btn.disabled = false;
            btn.textContent = '📤 Send Response';
        });
}

// Live preview for coach attachment
document.addEventListener('DOMContentLoaded', function () {
    var coachFileInput = document.getElementById('coachAttachment');
    if (!coachFileInput) return;

    coachFileInput.addEventListener('change', function () {
        var preview  = document.getElementById('coachAttachmentPreview');
        var nameEl   = document.getElementById('coachAttachmentFileName');
        var sizeEl   = document.getElementById('coachAttachmentFileSize');
        var errorEl  = document.getElementById('coachAttachmentError');

        errorEl.style.display = 'none';

        if (this.files && this.files[0]) {
            var f = this.files[0];
            var sizeDisplay = f.size >= 1048576
                ? (f.size / 1048576).toFixed(2) + ' MB'
                : (f.size / 1024).toFixed(1) + ' KB';
            nameEl.textContent = f.name;
            sizeEl.textContent = '(' + sizeDisplay + ')';
            preview.style.display = 'flex';
        } else {
            preview.style.display = 'none';
        }
    });
});

function clearCoachAttachment() {
    document.getElementById('coachAttachment').value = '';
    document.getElementById('coachAttachmentPreview').style.display = 'none';
    document.getElementById('coachAttachmentError').style.display = 'none';
}

    // Student submit reply
function submitStudentReply() {
    var message = document.getElementById('studentMessage').value.trim();
    var btn = document.getElementById('studentSubmitBtn');
    var fileInput = document.getElementById('followupAttachment');

    if (!message) {
        alert('Please write a message before sending.');
        return;
    }

    // Validate file if one is selected
    var file = fileInput.files[0];
    if (file) {
        var allowedTypes = ['image/jpeg','image/png','image/gif','application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        var maxSize = 5 * 1024 * 1024; // 5MB

        if (!allowedTypes.includes(file.type)) {
            document.getElementById('attachmentError').style.display = 'block';
            document.getElementById('attachmentError').textContent = '❌ File type not allowed. Use JPG, PNG, PDF, DOC, DOCX, XLS, or XLSX.';
            return;
        }
        if (file.size > maxSize) {
            document.getElementById('attachmentError').style.display = 'block';
            document.getElementById('attachmentError').textContent = '❌ File is too large. Maximum size is 5MB.';
            return;
        }
    }

    btn.disabled = true;
    btn.textContent = 'Sending...';

    var formData = new FormData();
    formData.append('tracking_id', '<?php echo $tracking_id; ?>');
    formData.append('message', message);
    if (file) {
        formData.append('attachment', file);
    }

    fetch('../../api/submit-student-reply.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                var msg = '✅ Follow-up sent successfully!';
                if (data.status_reopened) {
                    msg += '\n\nThis concern has been reopened for further review.';
                }
                if (data.attachment_saved) {
                    msg += '\n📎 Attachment uploaded successfully.';
                }
                if (data.attachment_error) {
                    msg += '\n⚠️ Note: ' + data.attachment_error;
                }
                alert(msg);
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
                btn.disabled = false;
                btn.textContent = '💬 Send Follow-up';
            }
        })
        .catch(err => {
            alert('❌ Connection error. Please try again.');
            btn.disabled = false;
            btn.textContent = '💬 Send Follow-up';
        });
}

// Show file preview when a file is selected
document.addEventListener('DOMContentLoaded', function () {
    var fileInput = document.getElementById('followupAttachment');
    if (!fileInput) return;

    fileInput.addEventListener('change', function () {
        var preview = document.getElementById('attachmentPreview');
        var nameEl = document.getElementById('attachmentFileName');
        var sizeEl = document.getElementById('attachmentFileSize');
        var errorEl = document.getElementById('attachmentError');

        errorEl.style.display = 'none';

        if (this.files && this.files[0]) {
            var f = this.files[0];
            var sizeKB = (f.size / 1024).toFixed(1);
            var sizeDisplay = f.size >= 1048576 ? (f.size / 1048576).toFixed(2) + ' MB' : sizeKB + ' KB';
            nameEl.textContent = f.name;
            sizeEl.textContent = '(' + sizeDisplay + ')';
            preview.style.display = 'flex';
        } else {
            preview.style.display = 'none';
        }
    });
});

function clearAttachment() {
    document.getElementById('followupAttachment').value = '';
    document.getElementById('attachmentPreview').style.display = 'none';
    document.getElementById('attachmentError').style.display = 'none';
}
    </script>
</body>
</html>