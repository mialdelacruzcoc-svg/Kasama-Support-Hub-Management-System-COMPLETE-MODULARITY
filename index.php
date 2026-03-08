<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasama Support Hub - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/shared-styles.css">
    <link rel="stylesheet" href="css/index-styles.css">
    <link rel="stylesheet" href="css/dark-mode.css">
    <script src="js/dark-mode.js"></script>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-container">
            
            <div class="login-left">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
                <div class="shape shape-4"></div>
                
                <div class="content-wrapper">
                    <div class="college-header-block">
                        <img src="images/coc-logo.png" alt="COC Logo" class="coc-logo">
                        <h1>Cagayan De Oro College</h1>
                        <p class="address">Max Suniel St. Carmen, Cagayan de Oro City, Misamis Oriental, Philippines 9000</p>
                    </div>
                    <div class="support-info">
                        <h2 class="support-tagline">Your concerns matter. We're here to help.</h2>
                        <ul class="support-features">
                            <li>Submit concerns confidentially</li>
                            <li>Get personalized guidance from coaches</li>
                            <li>Access resources and FAQs</li>
                            <li>Book one-on-one appointments</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="login-right">
                <div class="phinma-header">
                    <img src="images/phinma-logo.png" alt="PHINMA" class="phinma-logo">
                    <div class="phinma-info">
                        <div class="phinma-title">PHINMA EDUCATION</div>
                        <div class="phinma-subtitle">MAKING LIVES BETTER THROUGH EDUCATION</div>
                        <div class="kasama-title">Kasama Support Hub</div>
                    </div>
                </div>

                <div class="signin-section">
                    <h2>Sign In</h2>
                    
                    <form id="loginForm">
                        <div class="input-group">
                            <label>* Username</label>
                            <input type="text" id="username" placeholder="Enter Username" required>
                        </div>
                        
                        <div class="input-group">
                            <label>* Password</label>
                            <div class="password-wrapper">
                                <input type="password" id="password" placeholder="Enter Password" required>
                                <button type="button" class="toggle-password" onclick="togglePass('password', this)" title="Show password">👁</button>
                            </div>
                        </div>

                        <div class="captcha-group">
                            <span class="captcha-num" id="captchaNum1">--</span>
                            <span class="captcha-op">+</span>
                            <span class="captcha-num" id="captchaNum2">-</span>
                            <span class="captcha-op">=</span>
                            <input type="text" class="captcha-answer" id="captchaAnswer" maxlength="3" inputmode="numeric" autocomplete="off" required placeholder="?">
                            <button type="button" class="captcha-refresh" id="captchaRefresh" title="New numbers">🔄</button>
                        </div>
                        
                        <button type="submit" class="btn-signin">Sign In </button>
                        
                        <div class="register-link-container">
                            <p> Still don't have an account? <span id="openRegister" class="btn-register-open">Register Here!</span></p>
                            <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <div id="registerModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeModal">&times;</span>
            <h2 style="margin-bottom: 20px; color: #1a4a72;">Student Registration</h2>
            <p style="font-size: 14px; color: #666; margin-bottom: 20px;">Step 1: Verify your PHINMA Email</p>
            <form id="registerForm">
                <div class="reg-input-group">
                    <label>Student ID (e.g., 03-2223-012345)</label>
                    <input type="text" name="student_id" id="reg_student_id" required placeholder="Enter ID" maxlength="15">
                </div>
                <div class="reg-input-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="reg_name" required placeholder="Juan Dela Cruz">
                </div>
                <div class="reg-input-group">
                    <label>PHINMA Email</label>
                    <input type="email" name="email" id="reg_email" required placeholder="name.coc@phinmaed.com">
                </div>
                <div class="reg-input-group">
                    <label>Year Level</label>
                    <select name="year_level" id="reg_year_level" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-size: 14px; background: white; color: #333;">
                        <option value="" disabled selected>-- Select Year Level --</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                </div>
                <button type="submit" id="regSubmitBtn" class="btn-submit-reg">Get Verification Code 📧</button>
            </form>
        </div>
    </div>


    <script src="js/index.js"></script>
</body>
</html>