<?php
// ============================================
// EMAIL CONFIGURATION
// ============================================

// SMTP Settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'mial.delacruz.coc@phinmaed.com');
define('SMTP_PASSWORD', 'xjzs maxf wwmh qzty'); // ← PASTE NEW APP PASSWORD HERE (keep the spaces)
define('SMTP_FROM_EMAIL', 'mial.delacruz.coc@phinmaed.com');
define('SMTP_FROM_NAME', 'Kasama Support Hub');

// Email Settings
define('VERIFY_CODE_EXPIRY_MINUTES', 15);
define('MAX_CODE_ATTEMPTS', 3);
define('MAX_CODE_REQUESTS_PER_HOUR', 3);

// System Settings
define('SYSTEM_NAME', 'Kasama Support Hub');
define('SYSTEM_URL', 'http://localhost/kasama/kasama');
define('SUPPORT_EMAIL', 'hannah@coc.edu');
?>