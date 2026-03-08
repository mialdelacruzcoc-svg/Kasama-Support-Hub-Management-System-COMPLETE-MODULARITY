// JavaScript for index.php

// --- MATH CAPTCHA LOGIC ---
    let captchaA, captchaB;
    function generateCaptcha() {
        captchaA = Math.floor(Math.random() * 90) + 10; // 10-99 (2 digits)
        captchaB = Math.floor(Math.random() * 9) + 1;   // 1-9  (1 digit)
        document.getElementById('captchaNum1').textContent = captchaA;
        document.getElementById('captchaNum2').textContent = captchaB;
        document.getElementById('captchaAnswer').value = '';
    }
    generateCaptcha();
    document.getElementById('captchaRefresh').addEventListener('click', generateCaptcha);

    // --- LOGIN LOGIC ---
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate CAPTCHA first
        const userAnswer = parseInt(document.getElementById('captchaAnswer').value, 10);
        if (isNaN(userAnswer) || userAnswer !== (captchaA + captchaB)) {
            alert('Incorrect answer. Please solve the math problem.');
            generateCaptcha();
            return;
        }
        
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        
        const submitBtn = this.querySelector('.btn-signin');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Logging in...';
        submitBtn.disabled = true;
        
        fetch('api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Welcome, ' + data.data.name + '!');
                window.location.href = data.data.redirect;
            } else {
                alert('Error: ' + data.message);
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                generateCaptcha();
            }
        })
        .catch(error => {
            alert('Login failed. Please try again.');
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            generateCaptcha();
        });
    });

    // --- REGISTRATION MODAL LOGIC ---
    const modal = document.getElementById('registerModal');
    const openBtn = document.getElementById('openRegister');
    const closeBtn = document.getElementById('closeModal');

    openBtn.onclick = () => modal.style.display = 'block';
    closeBtn.onclick = () => modal.style.display = 'none';
    window.onclick = (e) => { if (e.target == modal) modal.style.display = 'none'; }

    // --- AUTO-FORMAT STUDENT ID ---
    const regStudentId = document.getElementById('reg_student_id');
    regStudentId.addEventListener('input', (e) => {
        let v = e.target.value.replace(/\D/g, ''); // Remove non-digits
        if (v.length > 2) v = v.slice(0,2) + '-' + v.slice(2);
        if (v.length > 7) v = v.slice(0,7) + '-' + v.slice(7);
        if (v.length > 15) v = v.slice(0, 15); // Max: XX-XXXX-XXXXXX
        e.target.value = v;
    });

    // --- REGISTRATION SUBMIT ---
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('regSubmitBtn');
        const email = document.getElementById('reg_email').value;
        const name = document.getElementById('reg_name').value;
        const studentId = document.getElementById('reg_student_id').value;
        const yearLevel = document.getElementById('reg_year_level').value;

        // Validate year level
        if (!yearLevel) {
            alert('Please select your year level.');
            return;
        }

        // Validate Student ID format (5 or 6 digits at end)
        const idPattern = /^\d{2}-\d{4}-\d{5,6}$/;
        if (!idPattern.test(studentId)) {
            alert('Invalid Student ID format. Use format: 03-2223-01234 or 03-2223-012345');
            return;
        }

        btn.textContent = 'Sending Code...';
        btn.disabled = true;

        const formData = new FormData(this);

        fetch('api/send-verification-code.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                sessionStorage.setItem('reg_email', email);
                sessionStorage.setItem('reg_name', name);
                sessionStorage.setItem('reg_id', studentId);
                sessionStorage.setItem('reg_year_level', yearLevel);
                
                alert('Verification code sent! Please check your email.');
                window.location.href = 'verify-code.php';
            } else {
                alert(data.message);
                btn.textContent = 'Get Verification Code 📧';
                btn.disabled = false;
            }
        })
        .catch(error => {
            alert('Registration error. Please try again.');
            btn.textContent = 'Get Verification Code 📧';
            btn.disabled = false;
        });
    });

    // Password visibility toggle
    function togglePass(inputId, btn) {
        const input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text';
            btn.textContent = '🙈';
            btn.title = 'Hide password';
        } else {
            input.type = 'password';
            btn.textContent = '👁';
            btn.title = 'Show password';
        }
    }
