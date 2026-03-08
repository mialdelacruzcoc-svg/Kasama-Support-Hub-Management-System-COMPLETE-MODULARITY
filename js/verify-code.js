// JavaScript for verify-code.php

// FIX: Use correct sessionStorage keys
    const email = sessionStorage.getItem('reg_email');
    const studentId = sessionStorage.getItem('reg_id');
    const studentName = sessionStorage.getItem('reg_name');
    
    const displayEmail = document.getElementById('displayEmail');
    const otpInputs = document.querySelectorAll('.otp-input');
    const verifyForm = document.getElementById('verifyForm');
    const alertBox = document.getElementById('alertBox');

    // Redirect if no email found
    if (!email) {
        window.location.href = 'register.php';
    } else {
        displayEmail.textContent = email;
    }

    // Auto-focus and move to next input
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            if (e.target.value.length === 1 && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
    });

    // Handle Verification
    verifyForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const otp = Array.from(otpInputs).map(input => input.value).join('');
        const verifyBtn = document.getElementById('verifyBtn');
        
        if (otp.length !== 6) {
            showAlert('Please enter the complete 6-digit code', 'error');
            return;
        }
        
        verifyBtn.disabled = true;
        verifyBtn.textContent = 'Verifying...';

        try {
            const response = await fetch('api/verify-otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `email=${encodeURIComponent(email)}&otp=${encodeURIComponent(otp)}`
            });

            const data = await response.json();

            if (data.success) {
                showAlert('Email verified! Setting up your password...', 'success');
                setTimeout(() => {
                    window.location.href = 'setup-password.php';
                }, 1500);
            } else {
                showAlert(data.message || 'Invalid code. Please try again.', 'error');
                verifyBtn.disabled = false;
                verifyBtn.textContent = 'Verify Account';
            }
        } catch (error) {
            showAlert('Network error. Please try again.', 'error');
            verifyBtn.disabled = false;
            verifyBtn.textContent = 'Verify Account';
        }
    });

    function showAlert(message, type) {
        alertBox.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    }

    // Resend Timer Logic
    let timeLeft = 60;
    const resendTimer = document.getElementById('resendTimer');
    const resendLink = document.getElementById('resendLink');

    const timer = setInterval(() => {
        timeLeft--;
        resendTimer.textContent = `Resend in ${timeLeft}s`;
        if (timeLeft <= 0) {
            clearInterval(timer);
            resendTimer.style.display = 'none';
            resendLink.style.display = 'inline';
            resendLink.classList.remove('disabled');
        }
    }, 1000);

    // Resend Code Handler
    resendLink.addEventListener('click', async () => {
        if (resendLink.classList.contains('disabled')) return;
        
        resendLink.textContent = 'Sending...';
        resendLink.classList.add('disabled');
        
        try {
            const response = await fetch('api/send-verification-code.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `email=${encodeURIComponent(email)}&student_id=${encodeURIComponent(studentId)}&name=${encodeURIComponent(studentName)}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert('New code sent! Check your email.', 'success');
                // Reset timer
                timeLeft = 60;
                resendLink.style.display = 'none';
                resendTimer.style.display = 'inline';
            } else {
                showAlert(data.message || 'Failed to resend code.', 'error');
                resendLink.classList.remove('disabled');
                resendLink.textContent = 'Resend Code';
            }
        } catch (error) {
            showAlert('Network error.', 'error');
            resendLink.classList.remove('disabled');
            resendLink.textContent = 'Resend Code';
        }
    });
