// JavaScript for setup-password.php

const email = sessionStorage.getItem('reg_email');
        const student_id = sessionStorage.getItem('reg_id');
        const student_name = sessionStorage.getItem('reg_name') || '';
        const year_level = sessionStorage.getItem('reg_year_level') || '';

        if (!email) window.location.href = 'register.php';

        document.getElementById('passwordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const pass = document.getElementById('password').value;
            const confirm = document.getElementById('confirmPassword').value;

            if (pass !== confirm) {
                alert('Passwords do not match!');
                return;
            }

            const btn = document.getElementById('finishBtn');
            btn.disabled = true;
            btn.textContent = 'Saving account...';

            try {
                const response = await fetch('api/complete-registration.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `email=${encodeURIComponent(email)}&student_id=${encodeURIComponent(student_id)}&password=${encodeURIComponent(pass)}&name=${encodeURIComponent(student_name)}&year_level=${encodeURIComponent(year_level)}`
                });
                const data = await response.json();
                if (data.success) {
                    alert('Registration complete! Redirecting to login...');
                    sessionStorage.clear();
                    window.location.href = 'index.php';
                } else {
                    alert(data.message);
                    btn.disabled = false;
                    btn.textContent = 'Complete Registration';
                }
            } catch (error) {
                alert('Connection error. Please try again.');
                btn.disabled = false;
                btn.textContent = 'Complete Registration';
            }
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
