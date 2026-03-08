// JavaScript for register.php

const form = document.getElementById('registerForm');
        const studentIdInput = document.getElementById('studentId');
        const submitBtn = document.getElementById('submitBtn');

        // Auto-format Student ID (supports 5 or 6 digits at end)
        studentIdInput.addEventListener('input', (e) => {
            let v = e.target.value.replace(/\D/g, ''); // Remove non-digits
            if (v.length > 2) v = v.slice(0,2) + '-' + v.slice(2);
            if (v.length > 7) v = v.slice(0,7) + '-' + v.slice(7);
            if (v.length > 15) v = v.slice(0, 15); // Max: XX-XXXX-XXXXXX
            e.target.value = v;
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value.trim();
            const studentId = studentIdInput.value.trim();
            const name = document.getElementById('fullname').value.trim();
            const yearLevel = document.getElementById('yearLevel').value;

            if (!yearLevel) {
                 showAlert('Please select your year level.', 'error');
                 return;
}


            if (!email.endsWith('@phinmaed.com')) {
                showAlert('Please use your official @phinmaed.com email', 'error');
                return;
            }

            // Validate Student ID format (5 or 6 digits at end)
            const idPattern = /^\d{2}-\d{4}-\d{5,6}$/;
            if (!idPattern.test(studentId)) {
                showAlert('Invalid Student ID format. Use: 03-2223-01234 or 03-2223-012345', 'error');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending Code...';

            try {
                const response = await fetch('api/send-verification-code.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `email=${encodeURIComponent(email)}&student_id=${encodeURIComponent(studentId)}&name=${encodeURIComponent(name)}&year_level=${encodeURIComponent(yearLevel)}`
                });

                const data = await response.json();

                if (data.success) {
                sessionStorage.setItem('reg_email', email);
                sessionStorage.setItem('reg_id', studentId);
                sessionStorage.setItem('reg_name', name);
                sessionStorage.setItem('reg_year_level', yearLevel);
                    
                    window.location.href = 'verify-code.php';
                } else {
                    showAlert(data.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Get Verification Code 📧';
                }
            } catch (error) {
                showAlert('Network error. Please try again.', 'error');
                submitBtn.disabled = false;
            }
        });

        function showAlert(msg, type) {
            document.getElementById('alertBox').innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
        }
